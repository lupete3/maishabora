<?php

namespace Tests\Feature\Api\V1\Client;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuildsClientApiSchema;
use Tests\TestCase;

/**
 * Tests de la connexion de l'application mobile (module client).
 * Base SQLite en mémoire (voir BuildsClientApiSchema) : la base réelle
 * n'est jamais touchée.
 */
class AuthTest extends TestCase
{
    use BuildsClientApiSchema;

    private const LOGIN = '/api/v1/client/auth/login';

    private const LOGOUT = '/api/v1/client/auth/logout';

    private const ME = '/api/v1/client/me';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClientApiSchema();
    }

    private function login(User $user, string $password = 'Secret#2026', string $device = 'Tecno Spark 10')
    {
        return $this->postJson(self::LOGIN, [
            'code' => $user->code,
            'password' => $password,
            'device_name' => $device,
        ]);
    }

    public function test_un_membre_active_se_connecte_et_lit_son_profil(): void
    {
        $agent = $this->member(['role' => 'recouvreur', 'name' => 'Agent', 'postnom' => 'Ilunga']);
        $member = $this->member(['agent_id' => $agent->id]);

        $response = $this->login($member)
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('must_change_password', false)
            ->assertJsonPath('client.code', $member->code)
            ->assertJsonPath('client.agent.full_name', 'Agent Ilunga')
            ->assertJsonMissingPath('client.password');

        $this->freshRequest()
            ->withToken($response->json('token'))
            ->getJson(self::ME)
            ->assertOk()
            ->assertJsonPath('data.code', $member->code);
    }

    public function test_mauvais_mot_de_passe_et_code_inconnu_donnent_la_meme_reponse(): void
    {
        $member = $this->member();

        $this->login($member, 'mauvais-mdp')
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_credentials');

        $this->postJson(self::LOGIN, [
            'code' => 'INCONNU',
            'password' => 'Secret#2026',
            'device_name' => 'Tecno',
        ])->assertStatus(422)->assertJsonPath('code', 'invalid_credentials');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_le_personnel_ne_peut_pas_utiliser_la_connexion_client(): void
    {
        $admin = $this->member(['role' => 'admin']);

        $this->login($admin)
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_un_membre_non_active_pour_le_mobile_est_refuse(): void
    {
        $member = $this->member(['mobile_activated_at' => null]);

        $this->login($member)
            ->assertStatus(403)
            ->assertJsonPath('code', 'access_denied');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_membre_inactif_ou_suspendu_est_refuse(): void
    {
        $this->login($this->member(['status' => false]))->assertStatus(403);
        $this->login($this->member(['is_suspended' => true]))->assertStatus(403);
    }

    public function test_le_mot_de_passe_par_defaut_1234_oblige_a_changer_de_mot_de_passe(): void
    {
        $member = $this->member(['password' => Hash::make('1234')]);

        $response = $this->login($member, '1234')
            ->assertOk()
            ->assertJsonPath('must_change_password', true);

        // Le drapeau est enregistré en base
        $this->assertTrue($member->fresh()->must_change_password);

        // Le jeton reçu est limité : aucune donnée n'est accessible
        $this->freshRequest()
            ->withToken($response->json('token'))
            ->getJson(self::ME)
            ->assertStatus(403)
            ->assertJsonPath('code', 'password_change_required');
    }

    public function test_un_mot_de_passe_temporaire_donne_aussi_un_jeton_limite(): void
    {
        $member = $this->member(['must_change_password' => true]);

        $response = $this->login($member)->assertOk()->assertJsonPath('must_change_password', true);

        $this->freshRequest()
            ->withToken($response->json('token'))
            ->getJson(self::ME)
            ->assertStatus(403)
            ->assertJsonPath('code', 'password_change_required');
    }

    public function test_cinq_echecs_bloquent_la_connexion(): void
    {
        $member = $this->member();

        for ($i = 0; $i < 5; $i++) {
            $this->login($member, 'mauvais-mdp')->assertStatus(422);
        }

        // Même avec le bon mot de passe, le compte est bloqué temporairement
        $this->login($member)
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_attempts');
    }

    public function test_la_deconnexion_revoque_le_jeton(): void
    {
        $member = $this->member();
        $token = $this->login($member)->json('token');

        $this->freshRequest()->withToken($token)->postJson(self::LOGOUT)->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->freshRequest()->withToken($token)->getJson(self::ME)->assertStatus(401);
    }

    public function test_une_nouvelle_connexion_sur_le_meme_appareil_remplace_l_ancien_jeton(): void
    {
        $member = $this->member();

        $this->login($member, device: 'Tecno Spark 10')->assertOk();
        $this->login($member, device: 'Tecno Spark 10')->assertOk();
        $this->login($member, device: 'iPhone 13')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 2);
    }

    public function test_un_membre_suspendu_apres_connexion_perd_son_acces(): void
    {
        $member = $this->member();
        $token = $this->login($member)->json('token');

        $member->forceFill(['is_suspended' => true])->saveQuietly();

        $this->freshRequest()
            ->withToken($token)
            ->getJson(self::ME)
            ->assertStatus(403)
            ->assertJsonPath('code', 'access_denied');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sans_jeton_la_reponse_est_un_401_en_json(): void
    {
        $this->get(self::ME)
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_le_membre_peut_se_connecter_avec_son_code_court(): void
    {
        $member = $this->member(['code' => '3420250000000258']);

        foreach (['258', '000258', 'MB000258', 'mb 000258', ' MB-258 ', '3420250000000258'] as $saisie) {
            $this->postJson(self::LOGIN, [
                'code' => $saisie,
                'password' => 'Secret#2026',
                'device_name' => 'Tecno',
            ])
                ->assertOk()
                ->assertJsonPath('client.code', '3420250000000258')
                ->assertJsonPath('client.short_code', 'MB000258');
        }
    }

    public function test_le_code_court_fonctionne_quelle_que_soit_l_annee_d_adhesion(): void
    {
        $member = $this->member(['code' => '3420240000000077']);

        $this->postJson(self::LOGIN, ['code' => 'MB77', 'password' => 'Secret#2026', 'device_name' => 'Tecno'])
            ->assertOk()
            ->assertJsonPath('client.short_code', 'MB000077');
    }

    public function test_un_code_court_ambigu_est_refuse(): void
    {
        // Deux membres avec le même numéro sur deux années différentes
        // (cas anormal, par ex. import manuel) : on ne devine pas.
        $this->member(['code' => '3420240000000500']);
        $this->member(['code' => '3420250000000500']);

        $this->postJson(self::LOGIN, ['code' => '500', 'password' => 'Secret#2026', 'device_name' => 'Tecno'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_le_code_court_ne_trouve_pas_le_personnel(): void
    {
        $this->member(['code' => '3420250000000900', 'role' => 'caissier']);

        $this->postJson(self::LOGIN, ['code' => '900', 'password' => 'Secret#2026', 'device_name' => 'Tecno'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_varier_l_ecriture_du_code_ne_contourne_pas_le_blocage(): void
    {
        $member = $this->member(['code' => '3420250000000258']);

        foreach (['258', '000258', 'MB000258', 'mb258', '3420250000000258'] as $saisie) {
            $this->postJson(self::LOGIN, ['code' => $saisie, 'password' => 'mauvais', 'device_name' => 'Tecno'])
                ->assertStatus(422);
        }

        // 5 échecs au total, toutes écritures confondues : bloqué
        $this->postJson(self::LOGIN, ['code' => 'MB0258', 'password' => 'Secret#2026', 'device_name' => 'Tecno'])
            ->assertStatus(429);
    }

    public function test_un_code_provisoire_expire_est_refuse_a_la_connexion(): void
    {
        $member = $this->member([
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->subMinute(),
        ]);

        $this->login($member)
            ->assertStatus(403)
            ->assertJsonPath('code', 'temporary_password_expired');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->postJson(self::LOGIN, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'password', 'device_name']);
    }
}
