<?php

namespace Tests\Concerns;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Monolog\Handler\NullHandler;

/**
 * Base SQLite en mémoire pour les tests de l'API mobile client.
 * Les tables sont créées à la main (comme dans les autres tests du projet) :
 * la base réelle n'est jamais touchée.
 */
trait BuildsClientApiSchema
{
    /** Mot de passe des membres de test. */
    protected string $memberPassword = 'Secret#2026';

    private int $memberCounter = 0;

    protected function setUpClientApiSchema(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        // Le journal de sécurité n'écrit rien pendant les tests
        config(['logging.channels.daily' => ['driver' => 'monolog', 'handler' => NullHandler::class]]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('postnom')->nullable();
            $table->string('prenom')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('telephone')->nullable();
            $table->string('photo_profil')->nullable();
            $table->date('date_adhesion')->nullable();
            $table->string('password');
            $table->string('role')->default('membre');
            $table->boolean('status')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->timestamp('mobile_activated_at')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('temporary_password_expires_at')->nullable();
            $table->boolean('visible_account')->default(true);
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('currency');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('type')->default('current');
            $table->string('status')->default('Actif');
            $table->boolean('subscribed')->default(false);
            $table->boolean('can_withdraw_all')->default(false);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('agent_account_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('credit_id')->nullable();
            $table->unsignedBigInteger('membership_card_id')->nullable();
            $table->string('type');
            $table->string('currency');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Crée un membre actif, activé pour le mobile, avec un vrai mot de passe.
     */
    protected function member(array $overrides = []): User
    {
        $n = ++$this->memberCounter;

        return User::forceCreate(array_merge([
            // Même format qu'en production : 34 + année + numéro sur 10 chiffres
            'code' => '342025' . str_pad((string) $n, 10, '0', STR_PAD_LEFT),
            'name' => 'Kabila' . $n,
            'postnom' => 'Mwamba',
            'prenom' => 'Grace',
            'date_naissance' => '1990-03-15',
            'telephone' => '+243810000' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'password' => Hash::make($this->memberPassword),
            'role' => 'membre',
            'status' => true,
            'is_suspended' => false,
            'mobile_activated_at' => now(),
            'must_change_password' => false,
        ], $overrides));
    }

    protected function account(User $member, string $currency = 'CDF', string $type = 'current', float $balance = 0, string $status = 'Actif'): Account
    {
        return Account::forceCreate([
            'user_id' => $member->id,
            'currency' => $currency,
            'type' => $type,
            'balance' => $balance,
            'status' => $status,
        ]);
    }

    /**
     * Opération sur un compte membre. user_id = l'agent (comme en production).
     */
    protected function transaction(Account $account, string $type, float $amount, float $balanceAfter, array $overrides = []): Transaction
    {
        return Transaction::forceCreate(array_merge([
            'account_id' => $account->id,
            'user_id' => 999,
            'type' => $type,
            'currency' => $account->currency,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => null,
        ], $overrides));
    }

    /**
     * Connexion d'un membre et retour d'un jeton complet.
     */
    protected function tokenFor(User $member): string
    {
        return $member->createToken('Tecno', ['client'])->plainTextToken;
    }

    /**
     * Entre deux requêtes d'un même test, Laravel garde l'utilisateur
     * en mémoire : on l'oublie pour simuler un vrai nouvel appel.
     */
    protected function freshRequest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}
