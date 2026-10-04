<?php

namespace Tests\Unit;

use App\Models\TermDeposit;
use App\Models\TermDepositProduct;
use App\Services\TermDepositService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class TermDepositServiceTest extends TestCase
{
    public function test_it_calculates_maturity_from_calendar_months_without_overflow(): void
    {
        $product = new TermDepositProduct([
            'term_days' => 180,
            'term_months' => 6,
        ]);

        $maturityDate = (new TermDepositService())->calculateMaturityDate(
            CarbonImmutable::parse('2026-08-31'),
            $product
        );

        $this->assertSame('2027-02-28', $maturityDate->toDateString());
    }

    public function test_it_keeps_day_based_maturity_for_legacy_products(): void
    {
        $product = new TermDepositProduct(['term_days' => 30]);

        $maturityDate = (new TermDepositService())->calculateMaturityDate(
            CarbonImmutable::parse('2026-01-01'),
            $product
        );

        $this->assertSame('2026-01-31', $maturityDate->toDateString());
    }

    public function test_it_calculates_simple_interest_for_elapsed_days(): void
    {
        $termDeposit = new TermDeposit([
            'principal_amount' => 1000,
            'annual_interest_rate' => 12,
            'term_days' => 30,
            'opened_at' => '2026-01-01',
            'matures_at' => '2026-01-31',
        ]);

        $interest = (new TermDepositService())
            ->calculateInterest($termDeposit, CarbonImmutable::parse('2026-01-16'));

        $this->assertSame(4.93, $interest);
    }

    public function test_interest_is_capped_at_the_contract_maturity_date(): void
    {
        $termDeposit = new TermDeposit([
            'principal_amount' => 1000,
            'annual_interest_rate' => 12,
            'term_days' => 30,
            'opened_at' => '2026-01-01',
            'matures_at' => '2026-01-31',
        ]);

        $service = new TermDepositService();
        $atMaturity = $service->calculateInterest($termDeposit, CarbonImmutable::parse('2026-01-31'));
        $afterMaturity = $service->calculateInterest($termDeposit, CarbonImmutable::parse('2026-03-01'));

        $this->assertSame(9.86, $atMaturity);
        $this->assertSame($atMaturity, $afterMaturity);
    }

    public function test_monthly_interest_is_one_twelfth_of_the_annual_rate(): void
    {
        $interest = TermDepositService::calculateMonthlyInterest(1000000, 2);

        $this->assertSame(1666.6667, $interest);
    }
}
