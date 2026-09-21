<?php

namespace Tests\Unit;

use App\Services\WeeklyManagementReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WeeklyManagementReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'weekly_report_test', 'database.connections.weekly_report_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['code', 'name', 'postnom', 'prenom', 'sexe', 'telephone', 'role'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(1);
            $table->string('currency');
            $table->double('amount');
            $table->double('frais_credit')->default(3);
            $table->double('mutuelle')->default(1);
            $table->date('start_date');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type');
            $table->string('currency');
            $table->double('amount');
            $table->timestamps();
        });
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->integer('credit_id');
            $table->date('due_date');
            $table->dateTime('paid_date')->nullable();
            $table->boolean('is_paid')->default(true);
            foreach (['paid_amount', 'paid_principal', 'paid_interest', 'paid_penalty'] as $field) {
                $table->double($field)->default(0);
            }
        });
    }

    public function test_grants_use_creation_date_and_value_fees_as_a_percentage_of_principal(): void
    {
        foreach ([['CDF', 600000], ['USD', 700], ['USD', 700], ['CDF', 1000000], ['CDF', 700000], ['USD', 1000]] as [$currency, $amount]) {
            DB::table('credits')->insert([
                'currency' => $currency, 'amount' => $amount,
                'created_at' => '2026-09-18 15:30:00', 'start_date' => '2026-10-18',
            ]);
        }
        // An older grant with an installment in this week must not inflate grants.
        DB::table('credits')->insert([
            'currency' => 'USD', 'amount' => 9000,
            'created_at' => '2026-08-18 12:00:00', 'start_date' => '2026-09-18',
        ]);
        $report = (new WeeklyManagementReportService)->build('2026-09-12', '2026-09-18');
        $credits = $report['current']['granted_credits'];
        $this->assertSame(['CDF' => 3, 'USD' => 3], $credits['count']);
        $this->assertSame(['CDF' => 2300000.0, 'USD' => 2400.0], $credits['amount_total']);
        $this->assertSame(['CDF' => 69000.0, 'USD' => 72.0], $credits['fees_total']);
        $this->assertSame($credits['fees_total'], $report['current']['profitability']['products']['credit_fees']);
        $this->assertSame(['CDF' => 3.0, 'USD' => 3.0], $credits['mutuelle_total']);
        $this->assertSame('2026-09-05', $report['comparison_period']['start']->toDateString());
        $this->assertSame('2026-09-11', $report['comparison_period']['end']->toDateString());
    }

    public function test_both_boundary_days_are_included_and_following_saturday_is_excluded(): void
    {
        foreach (['2026-09-11 23:59:59', '2026-09-12 00:00:00', '2026-09-18 23:59:59.999999', '2026-09-19 00:00:00'] as $date) {
            $userId = DB::table('users')->insertGetId(['role' => 'membre', 'created_at' => $date]);
            $creditId = DB::table('credits')->insertGetId([
                'user_id' => $userId, 'currency' => 'USD', 'amount' => 100,
                'created_at' => $date, 'start_date' => '2026-10-18',
            ]);
            DB::table('transactions')->insert([
                'user_id' => $userId, 'type' => 'dépôt', 'currency' => 'USD', 'amount' => 20, 'created_at' => $date,
            ]);
            DB::table('repayments')->insert([
                'credit_id' => $creditId, 'due_date' => '2026-09-18', 'paid_date' => $date,
                'paid_amount' => 11, 'paid_principal' => 10, 'paid_interest' => 1,
            ]);
        }
        $report = (new WeeklyManagementReportService)->build('2026-09-12', '2026-09-18');
        $current = $report['current'];
        $this->assertSame(2, $current['new_clients']['total']);
        $this->assertSame(200.0, $current['granted_credits']['amount_total']['USD']);
        $this->assertSame(40.0, $current['deposits_withdrawals']['deposits']['USD']);
        $this->assertSame(22.0, $current['repayments']['paid_total']['USD']);
        $this->assertSame(100.0, $report['previous']['granted_credits']['amount_total']['USD']);
        $extended = (new WeeklyManagementReportService)->build('2026-09-12', '2026-09-19');
        $this->assertSame(300.0, $extended['current']['granted_credits']['amount_total']['USD']);
    }
}
