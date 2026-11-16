<?php

namespace Tests\Feature;

use App\Livewire\Members\MobileAccess;
use App\Models\UserLog;
use App\Services\MobileAccessService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsClientApiSchema;
use Tests\TestCase;

/**
 * Tests de l'activation de l'accès mobile côté agence
 * (service + bloc Livewire de la fiche membre).
 */
class MobileAccessTest extends TestCase
{
    use BuildsClientApiSchema;

    private MobileAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClientApiSchema();
        $this->service = app(MobileAccessService::class);
    }

    public function test_l_activation_genere_un_code_provisoire_de_6_chiffres(): void
    {
        $member = $this->member(['mobile_activated_at' => null]);
        $member->createToken('ancien appareil');

        $code = $this->service->activate($member);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $member->refresh();
        $this->assertTrue(Hash::check($code, $member->password));     // seul le hash est stocké
        $this->assertNotSame($code, $member->password);
        $this->assertTrue($member->must_change_password);
        $this->assertNotNull($member->mobile_activated_at);
        $this->assertTrue($member->temporary_password_expires_at->between(
            now()->addHours(MobileAccessService::TEMP_CODE_HOURS - 1),
            now()->addHours(MobileAccessService::TEMP_CODE_HOURS + 1),
        ));
        $this->assertSame(0, $member->tokens()->count());             // appareils déconnectés
    }

    public function test_un_nouveau_code_conserve_la_date_de_premiere_activation(): void
    {
        $firstActivation = now()->subMonth()->startOfSecond();
        $member = $this->member(['mobile_activated_at' => $firstActivation]);

        $this->service->activate($member);

        $this->assertTrue($member->fresh()->mobile_activated_at->equalTo($firstActivation));
    }

    public function test_le_personnel_ne_peut_pas_recevoir_d_acces_mobile(): void
    {
        $this->expectException(DomainException::class);

        $this->service->activate($this->member(['role' => 'caissier']));
    }

    public function test_un_membre_inactif_ne_peut_pas_etre_active(): void
    {
        $this->expectException(DomainException::class);

        $this->service->activate($this->member(['status' => false]));
    }

    public function test_la_desactivation_coupe_l_acces_et_deconnecte(): void
    {
        $member = $this->member();
        $member->createToken('Tecno');

        $this->service->deactivate($member);

        $member->refresh();
        $this->assertNull($member->mobile_activated_at);
        $this->assertSame(0, $member->tokens()->count());
    }

    /**
     * Agent connecté, avec ou sans la permission gerer-acces-mobile.
     */
    private function actingAsAgent(bool $allowed): void
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action');
            $table->string('device')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Tables minimales du paquet spatie/laravel-permission
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::create(['name' => 'gerer-acces-mobile', 'guard_name' => 'web']);

        $agent = $this->member(['role' => 'caissier', 'code' => null]);
        if ($allowed) {
            $agent->givePermissionTo('gerer-acces-mobile');
        }

        $this->actingAs($agent);
    }

    public function test_le_bloc_de_la_fiche_affiche_le_code_une_seule_fois(): void
    {
        $this->actingAsAgent(allowed: true);
        $member = $this->member(['mobile_activated_at' => null, 'code' => '3420250000000258']);

        $component = Livewire::test(MobileAccess::class, ['memberId' => $member->id])
            ->assertSee('Non activé')
            ->assertSee('MB000258')
            ->call('activate')
            ->assertSet('showCodeModal', true);

        $code = $component->get('temporaryCode');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $component->assertSee($code);

        // L'action est tracée dans le journal des utilisateurs, sans le code
        $log = UserLog::sole();
        $this->assertSame('activation_acces_mobile', $log->action);
        $this->assertStringNotContainsString($code, $log->description);

        // Fermer la modale efface définitivement le code
        $component->call('closeCodeModal')
            ->assertSet('temporaryCode', null)
            ->assertDontSee($code)
            ->assertSee('En attente');
    }

    public function test_le_bloc_est_interdit_sans_la_permission(): void
    {
        $this->actingAsAgent(allowed: false);
        $member = $this->member();

        Livewire::test(MobileAccess::class, ['memberId' => $member->id])->assertStatus(403);
    }
}
