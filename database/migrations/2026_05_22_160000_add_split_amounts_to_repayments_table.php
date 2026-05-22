<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            $table->decimal('principal_amount', 15, 2)->nullable()->after('expected_amount');
            $table->decimal('interest_amount', 15, 2)->nullable()->after('principal_amount');
            $table->decimal('paid_principal', 15, 2)->default(0.00)->after('paid_amount');
            $table->decimal('paid_interest', 15, 2)->default(0.00)->after('paid_principal');
            $table->decimal('paid_penalty', 15, 2)->default(0.00)->after('paid_interest');
            $table->date('last_penalty_calculation_date')->nullable()->after('is_paid');
        });

        // Migrate historical data
        DB::transaction(function () {
            $credits = DB::table('credits')->get();
            foreach ($credits as $credit) {
                $repayments = DB::table('repayments')
                    ->where('credit_id', $credit->id)
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->get();
                
                $installments = count($repayments);
                if ($installments === 0) {
                    continue;
                }

                if ($credit->credit_type === 'degressif') {
                    $capitalPart = $credit->amount / $installments;
                    $remainingCapital = $credit->amount;

                    foreach ($repayments as $index => $repayment) {
                        $interest = $remainingCapital * ($credit->interest_rate / 100);
                        
                        $principal = $capitalPart;
                        $interestAmount = $interest;

                        if ($index === $installments - 1) {
                            $principal = $remainingCapital;
                            $interestAmount = $repayment->expected_amount - $principal;
                        }

                        $update = [
                            'principal_amount' => round($principal, 2),
                            'interest_amount' => round($interestAmount, 2),
                            'last_penalty_calculation_date' => $repayment->due_date,
                        ];

                        $paidVal = floatval($repayment->paid_amount);
                        if ($repayment->is_paid) {
                            $update['paid_principal'] = round($principal, 2);
                            $update['paid_interest'] = round($interestAmount, 2);
                            $update['paid_penalty'] = $repayment->penalty;
                        } elseif ($paidVal > 0) {
                            // Allocate paid_amount: penalty first, then interest, then principal
                            $rem = $paidVal;
                            $allocatedPenalty = min($rem, floatval($repayment->penalty));
                            $rem -= $allocatedPenalty;
                            
                            $allocatedInterest = min($rem, round($interestAmount, 2));
                            $rem -= $allocatedInterest;
                            
                            $allocatedPrincipal = min($rem, round($principal, 2));
                            
                            $update['paid_penalty'] = $allocatedPenalty;
                            $update['paid_interest'] = $allocatedInterest;
                            $update['paid_principal'] = $allocatedPrincipal;
                        }

                        DB::table('repayments')->where('id', $repayment->id)->update($update);
                        $remainingCapital -= $capitalPart;
                    }
                } else {
                    // constant
                    $monthlyCapital = round($credit->amount / $installments, 2);
                    $monthlyInterest = round($credit->amount * ($credit->interest_rate / 100), 2);
                    $remainingCapital = $credit->amount;

                    foreach ($repayments as $index => $repayment) {
                        $principal = $monthlyCapital;
                        $interestAmount = $monthlyInterest;

                        if ($index === $installments - 1) {
                            $principal = $remainingCapital;
                            $interestAmount = $repayment->expected_amount - $principal;
                        }

                        $update = [
                            'principal_amount' => round($principal, 2),
                            'interest_amount' => round($interestAmount, 2),
                            'last_penalty_calculation_date' => $repayment->due_date,
                        ];

                        $paidVal = floatval($repayment->paid_amount);
                        if ($repayment->is_paid) {
                            $update['paid_principal'] = round($principal, 2);
                            $update['paid_interest'] = round($interestAmount, 2);
                            $update['paid_penalty'] = $repayment->penalty;
                        } elseif ($paidVal > 0) {
                            // Allocate paid_amount: penalty first, then interest, then principal
                            $rem = $paidVal;
                            $allocatedPenalty = min($rem, floatval($repayment->penalty));
                            $rem -= $allocatedPenalty;
                            
                            $allocatedInterest = min($rem, round($interestAmount, 2));
                            $rem -= $allocatedInterest;
                            
                            $allocatedPrincipal = min($rem, round($principal, 2));
                            
                            $update['paid_penalty'] = $allocatedPenalty;
                            $update['paid_interest'] = $allocatedInterest;
                            $update['paid_principal'] = $allocatedPrincipal;
                        }

                        DB::table('repayments')->where('id', $repayment->id)->update($update);
                        $remainingCapital -= $principal;
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repayments', function (Blueprint $table) {
            $table->dropColumn([
                'principal_amount',
                'interest_amount',
                'paid_principal',
                'paid_interest',
                'paid_penalty',
                'last_penalty_calculation_date'
            ]);
        });
    }
};
