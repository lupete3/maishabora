<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CreditStatsService;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreditStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_global_stats_returns_correct_structure()
    {
        $service = new CreditStatsService();
        $stats = $service->getGlobalStats();

        $this->assertArrayHasKey('USD', $stats);
        $this->assertArrayHasKey('CDF', $stats);
        $this->assertArrayHasKey('recoveryRate', $stats['USD']);
    }

    public function test_calculation_logic()
    {
        // Création d'un utilisateur sans utiliser de factory si possible (pour éviter les problèmes si elles sont mal configurées)
        $user = User::create([
            'name' => 'Test',
            'postnom' => 'User',
            'date_naissance' => '1990-01-01',
            'telephone' => '0000000000',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'membre',
        ]);

        // Création d'un compte
        $account = \App\Models\Account::create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 0,
        ]);

        // Création d'un crédit
        $credit = Credit::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'currency' => 'USD',
            'amount' => 1000,
            'interest_rate' => 10,
            'installments' => 1,
            'is_paid' => false,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'credit_type' => 'constant',
            'repayment_type' => 'monthly',
        ]);

        // Création d'un remboursement
        Repayment::create([
            'credit_id' => $credit->id,
            'due_date' => now()->addDays(30),
            'expected_amount' => 1100, // 1000 + 100 (10% de 1000)
            'paid_amount' => 550,
            'is_paid' => false,
            'total_due' => 1100,
            'penalty' => 0,
        ]);

        $service = new CreditStatsService();
        $stats = $service->getGlobalStats();

        // Vérifications
        $this->assertEquals(1000, $stats['USD']['totalCreditsValue']);
        $this->assertEquals(1100, $stats['USD']['totalToRepayValue']);
        $this->assertEquals(550, $stats['USD']['totalRepaidValue']);
        $this->assertEquals(50, $stats['USD']['recoveryRate']);
        // Marge d'intérêt : ((1100 - 1000) / 1000) * 100 = 10%
        $this->assertEquals(10, $stats['USD']['interestMargin']);
        $this->assertEquals(550, $stats['USD']['remainingBalanceValue']);
    }
}
