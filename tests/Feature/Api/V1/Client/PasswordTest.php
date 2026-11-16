<?php

namespace Tests\Feature\Api\V1\Client;

use App\Models\User;
use App\Services\MobileAccessService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsClientApiSchema;
use Tests\TestCase;

/**
 * Tests du changement de mot de passe depuis l'application mobile.
 */
class PasswordTest extends TestCase
{
    use BuildsClientApiSchema;

    private const LOGIN = '/api/v1/client/auth/login';

    private const PASSWORD = '/api/v1/client/password';

    private const ME = '/api/v1/client/me';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClientApiSchema();
    }

    private function loginToken(User $user, string $password, string $device = 'Tecno Spark 10'): string
    {
        return $this->postJson(self::LOGIN, [
            'code' => $user->code,
            'password' => $password,
            'device_name' => $device,
        ])->assertOk()->json('token');
    }

    private function changePassword(string $token, string $current, string $new, ?string $confirmation = null)
    {
        return $this->freshRequest()->withToken($token)->putJson(self::PASSWORD, [
            'current_password' => $current,
            'password' => $new,
            'password_confirmation' => $confirmation ?? $new,
        ]);
    }

    public function test_parcours_complet_activation_en_agence_puis_premier_changement(): void
    {
        // 1. L'agence active l'accès : le membre reçoit un code provisoire
        $member = $this->member(['mobile_activated_at' => null]);
        $code = app(MobileAccessService::class)->activate($member);

        // 2. Connexion avec le code : jeton limité
        $limited = $this->freshRequest()->postJson(self::LOGIN, [
            'code' => $member->code, 'password' => $code, 'device_name' => 'Tecno',
        ])->assertOk()->assertJsonPath('must_change_password', true)->json('token');

        $this->freshRequest()->withToken($limited)->getJson(self::ME)->assertStatus(403);

        // 3. Le membre choisit son mot de passe : il reçoit un jeton complet
        $full = $this->changePassword($limited, $code, 'Kivu#2026')
            ->assertOk()
            ->assertJsonPath('must_change_password', false)
            ->json('token');

        $this->freshRequest()->withToken($full)->getJson(self::ME)->assertOk();

        // 4. L'ancien jeton limité ne fonctionne plus
        $this->freshRequest()->withToken($limited)->getJson(self::ME)->assertStatus(401);

        // 5. En base : plus de code provisoire
        $member->refresh();
        $this->assertFalse($member->must_change_password);
        $this->assertNull($member->temporary_password_expires_at);
        $this->assertTrue(Hash::check('Kivu#2026', $member->password));

        // 6. Le code provisoire ne permet plus de se connecter
        $this->freshRequest()->postJson(self::LOGIN, [
            'code' => $member->code, 'password' => $code, 'device_name' => 'Tecno',
        ])->assertStatus(422);
    }

    public function test_un_membre_avec_1234_peut_remplacer_son_mot_de_passe(): void
    {
        $member = $this->member(['password' => Hash::make('1234')]);
        $limited = $this->loginToken($member, '1234');

        $this->changePassword($limited, '1234', '582917')->assertOk();

        $this->assertFalse($member->fresh()->must_change_password);
    }

    public function test_le_mot_de_passe_actuel_doit_etre_correct(): void
    {
        $member = $this->member();
        $token = $this->loginToken($member, $this->memberPassword);

        $this->changePassword($token, 'mauvais', 'Kivu#2026')
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_current_password');

        $this->assertTrue(Hash::check($this->memberPassword, $member->fresh()->password));
    }

    public function test_les_mots_de_passe_faibles_sont_refuses(): void
    {
        // On teste ici les règles, pas la limite de 5 essais par minute
        $this->withoutMiddleware(ThrottleRequests::class);

        $member = $this->member(['telephone' => '+243899517362', 'date_naissance' => '1990-03-15']);
        $token = $this->loginToken($member, $this->memberPassword);

        $faibles = [
            '12345',         // trop court
            '123456',        // suite
            '654321',        // suite inverse
            'abcdef',        // suite de lettres
            '111111',        // caractère répété
            'azerty',        // mot courant
            '15031990',      // date de naissance jjmmaaaa
            'x150390',       // date de naissance jjmmaa
            'abc517362',     // fin du téléphone
            'x000001',       // numéro court du membre
            'MB000001!',     // code court du membre
            $this->memberPassword, // identique à l'actuel
        ];

        foreach ($faibles as $faible) {
            $this->changePassword($token, $this->memberPassword, $faible)
                ->assertStatus(422)
                ->assertJsonValidationErrors('password');
        }

        // Confirmation différente
        $this->changePassword($token, $this->memberPassword, 'Kivu#2026', 'Kivu#2027')
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        // Rien n'a changé
        $this->assertTrue(Hash::check($this->memberPassword, $member->fresh()->password));
    }

    public function test_un_changement_volontaire_deconnecte_les_autres_appareils(): void
    {
        $member = $this->member();
        $phone = $this->loginToken($member, $this->memberPassword, 'Tecno Spark 10');
        $tablet = $this->loginToken($member, $this->memberPassword, 'Samsung Tab');

        $newPhoneToken = $this->changePassword($phone, $this->memberPassword, 'Kivu#2026')
            ->assertOk()
            ->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->freshRequest()->withToken($tablet)->getJson(self::ME)->assertStatus(401);
        $this->freshRequest()->withToken($newPhoneToken)->getJson(self::ME)->assertOk();
    }

    public function test_un_code_provisoire_expire_ne_permet_pas_de_changer_le_mot_de_passe(): void
    {
        $member = $this->member(['mobile_activated_at' => null]);
        $code = app(MobileAccessService::class)->activate($member);
        $limited = $this->loginToken($member, $code);

        // Le membre attend trop longtemps avant de choisir son mot de passe
        $this->travel(MobileAccessService::TEMP_CODE_HOURS + 1)->hours();

        $this->changePassword($limited, $code, 'Kivu#2026')
            ->assertStatus(403)
            ->assertJsonPath('code', 'temporary_password_expired');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sans_jeton_le_changement_est_refuse(): void
    {
        $this->putJson(self::PASSWORD, [])->assertStatus(401);
    }

    public function test_cinq_essais_par_minute_maximum(): void
    {
        $member = $this->member();
        $token = $this->loginToken($member, $this->memberPassword);

        for ($i = 0; $i < 5; $i++) {
            $this->changePassword($token, 'mauvais', 'Kivu#2026')->assertStatus(422);
        }

        $this->changePassword($token, $this->memberPassword, 'Kivu#2026')->assertStatus(429);
    }
}
