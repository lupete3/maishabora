<?php

namespace App\Services;

use App\Models\Account;
use App\Models\TermDeposit;
use App\Models\TermDepositProduct;
use App\Models\TermDepositTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TermDepositService
{
    public function open(User $member, int $productId, int $sourceAccountId, float $amount, ?int $actorId = null): TermDeposit
    {
        return DB::transaction(function () use ($member, $productId, $sourceAccountId, $amount, $actorId) {
            if ($member->role !== 'membre' || !$member->status) {
                throw ValidationException::withMessages(['member' => 'Le membre doit être actif.']);
            }

            $product = TermDepositProduct::query()
                ->whereKey($productId)
                ->where('is_active', true)
                ->firstOrFail();
            $amount = round($amount, 2);

            if ($amount < (float) $product->minimum_amount || ($product->maximum_amount !== null && $amount > (float) $product->maximum_amount)) {
                throw ValidationException::withMessages(['amount' => 'Le montant est hors des limites autorisées pour ce produit.']);
            }

            $sourceAccount = Account::query()->whereKey($sourceAccountId)
                ->where('user_id', $member->id)
                ->whereIn('type', ['current', 'savings'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($sourceAccount->status !== 'Actif' || !in_array($sourceAccount->currency, ['USD', 'CDF'], true)) {
                throw ValidationException::withMessages(['source_account_id' => 'Le compte source doit être actif et utiliser une devise autorisée.']);
            }

            if ((float) $sourceAccount->balance < $amount) {
                throw ValidationException::withMessages(['amount' => 'Le solde du compte source est insuffisant.']);
            }

            $settlementAccount = Account::query()->where('user_id', $member->id)
                ->where('currency', $sourceAccount->currency)
                ->where('type', 'current')
                ->where('status', 'Actif')
                ->lockForUpdate()
                ->first();

            if (!$settlementAccount) {
                throw ValidationException::withMessages(['settlement_account' => 'Le membre doit avoir un compte courant actif dans la devise du placement.']);
            }

            $openedAt = CarbonImmutable::today();
            $termMonths = $product->term_months ? (int) $product->term_months : null;
            $maturesAt = $this->calculateMaturityDate($openedAt, $product);
            $termDays = (int) $openedAt->diffInDays($maturesAt);
            $sourceAccount->balance = round((float) $sourceAccount->balance - $amount, 2);
            $sourceAccount->save();

            $termDeposit = TermDeposit::create([
                'term_deposit_product_id' => $product->id,
                'user_id' => $member->id,
                'source_account_id' => $sourceAccount->id,
                'settlement_account_id' => $settlementAccount->id,
                'currency' => $sourceAccount->currency,
                'principal_amount' => $amount,
                'accrued_interest_amount' => 0,
                'last_interest_calculated_at' => $openedAt,
                'term_days' => $termDays,
                'term_months' => $termMonths,
                'annual_interest_rate' => $product->annual_interest_rate,
                'early_withdrawal_penalty_rate' => $product->early_withdrawal_penalty_rate,
                'early_withdrawal_interest_policy' => $product->early_withdrawal_interest_policy,
                'opened_at' => $openedAt,
                'matures_at' => $maturesAt,
                'status' => 'active',
                'opened_by' => $actorId ?? $member->id,
            ]);

            $reference = (string) Str::uuid();
            $this->recordTermOperation($termDeposit, 'opening', $amount, $amount, 'Ouverture du dépôt à terme', $reference, $actorId ?? $member->id);
            $this->recordAccountOperation($sourceAccount, $actorId ?? $member->id, 'placement_compte_terme', $amount, 'Placement à terme #' . $termDeposit->id);
            return $termDeposit;
        }, 3);
    }

    public function calculateMaturityDate(CarbonImmutable $openedAt, TermDepositProduct $product): CarbonImmutable
    {
        return $product->term_months
            ? $openedAt->addMonthsNoOverflow((int) $product->term_months)
            : $openedAt->addDays((int) $product->term_days);
    }

    public function withdrawEarly(int $termDepositId, ?int $actorId = null): TermDeposit
    {
        return DB::transaction(function () use ($termDepositId, $actorId) {
            $termDeposit = TermDeposit::query()->with('product')
                ->whereKey($termDepositId)->lockForUpdate()->firstOrFail();

            if ($termDeposit->status !== 'active' || $termDeposit->matures_at->isToday() || $termDeposit->matures_at->isPast()) {
                throw ValidationException::withMessages(['term_deposit' => 'Ce contrat n’est plus admissible à un retrait anticipé.']);
            }

            $this->accrueInterestTo($termDeposit, CarbonImmutable::today(), $actorId ?? $termDeposit->user_id);

            $account = Account::query()->whereKey($termDeposit->settlement_account_id)->lockForUpdate()->firstOrFail();
            if ($account->status !== 'Actif' || $account->currency !== $termDeposit->currency) {
                throw ValidationException::withMessages(['settlement_account' => 'Le compte courant de règlement n’est pas actif dans la devise du contrat.']);
            }

            $principal = (float) $termDeposit->principal_amount;
            $penalty = min($principal, round($principal * (float) $termDeposit->early_withdrawal_penalty_rate / 100, 2));
            $interest = $termDeposit->early_withdrawal_interest_policy === 'pro_rata'
                ? round((float) $termDeposit->accrued_interest_amount, 2)
                : 0.0;
            $netPrincipal = round($principal - $penalty, 2);
            $payout = round($netPrincipal + $interest, 2);

            $account->balance = round((float) $account->balance + $payout, 2);
            $account->save();

            $reference = (string) Str::uuid();
            $termDeposit->forceFill([
                'status' => 'early_withdrawn',
                'closed_at' => now(),
                'interest_amount' => $interest,
                'penalty_amount' => $penalty,
                'payout_amount' => $payout,
                'settlement_reference' => $reference,
            ])->save();

            $this->recordTermOperation($termDeposit, 'early_withdrawal_principal', $netPrincipal, 0, 'Remboursement anticipé du principal', $reference . '-PRINCIPAL', $actorId ?? $termDeposit->user_id);
            if ($interest > 0) {
                $this->recordTermOperation($termDeposit, 'early_withdrawal_interest', $interest, 0, 'Intérêts proratisés au retrait anticipé', $reference . '-INTEREST', $actorId ?? $termDeposit->user_id);
            }
            if ($penalty > 0) {
                $this->recordTermOperation($termDeposit, 'early_withdrawal_penalty', $penalty, 0, 'Pénalité de retrait anticipé', $reference . '-PENALTY', $actorId ?? $termDeposit->user_id);
            }

            $this->recordAccountOperation($account, $actorId ?? $termDeposit->user_id, 'retrait_anticipe_compte_terme', $payout, 'Règlement anticipé du dépôt à terme #' . $termDeposit->id);
            return $termDeposit->refresh();
        }, 3);
    }

    public function settleAtMaturity(int $termDepositId, ?int $actorId = null): bool
    {
        return DB::transaction(function () use ($termDepositId, $actorId) {
            $termDeposit = TermDeposit::query()->with('product')
                ->whereKey($termDepositId)->lockForUpdate()->firstOrFail();

            if (!in_array($termDeposit->status, ['active', 'matured'], true) || $termDeposit->matures_at->isFuture()) {
                return false;
            }

            $this->accrueInterestTo(
                $termDeposit,
                CarbonImmutable::parse($termDeposit->matures_at),
                $actorId ?? $termDeposit->user_id
            );

            $account = Account::query()->whereKey($termDeposit->settlement_account_id)->lockForUpdate()->firstOrFail();
            if ($account->status !== 'Actif' || $account->currency !== $termDeposit->currency) {
                throw ValidationException::withMessages(['settlement_account' => 'Le compte courant de règlement n’est pas actif dans la devise du contrat.']);
            }

            $principal = (float) $termDeposit->principal_amount;
            $interest = round((float) $termDeposit->accrued_interest_amount, 2);
            $payout = round($principal + $interest, 2);
            $account->balance = round((float) $account->balance + $payout, 2);
            $account->save();

            $reference = (string) Str::uuid();
            $termDeposit->forceFill([
                'status' => 'closed',
                'closed_at' => now(),
                'interest_amount' => $interest,
                'penalty_amount' => 0,
                'payout_amount' => $payout,
                'settlement_reference' => $reference,
            ])->save();

            $this->recordTermOperation($termDeposit, 'maturity_principal', $principal, 0, 'Règlement du principal à échéance', $reference . '-PRINCIPAL', $actorId ?? $termDeposit->user_id);
            if ($interest > 0) {
                $this->recordTermOperation($termDeposit, 'maturity_interest', $interest, 0, 'Règlement des intérêts à échéance', $reference . '-INTEREST', $actorId ?? $termDeposit->user_id);
            }

            $this->recordAccountOperation($account, $actorId ?? $termDeposit->user_id, 'echeance_compte_terme', $payout, 'Règlement du dépôt à terme #' . $termDeposit->id);
            return true;
        }, 3);
    }

    public function accrueDailyInterest(int $termDepositId, ?CarbonImmutable $asOf = null): float
    {
        return DB::transaction(function () use ($termDepositId, $asOf) {
            $termDeposit = TermDeposit::query()->whereKey($termDepositId)->lockForUpdate()->firstOrFail();
            if (!in_array($termDeposit->status, ['active', 'matured'], true)) {
                return 0.0;
            }

            $actorId = $termDeposit->opened_by ?? $termDeposit->user_id;
            return $this->accrueInterestTo($termDeposit, $asOf ?? CarbonImmutable::today(), $actorId);
        }, 3);
    }

    private function accrueInterestTo(TermDeposit $termDeposit, CarbonImmutable $asOf, int $actorId): float
    {
        $openedAt = CarbonImmutable::parse($termDeposit->opened_at)->startOfDay();
        $maturesAt = CarbonImmutable::parse($termDeposit->matures_at)->startOfDay();
        $endDate = $asOf->startOfDay()->min($maturesAt);
        $lastCalculatedAt = $termDeposit->last_interest_calculated_at
            ? CarbonImmutable::parse($termDeposit->last_interest_calculated_at)->startOfDay()
            : $openedAt;
        $startDate = $lastCalculatedAt->max($openedAt);
        $previousInterestCents = (int) round((float) $termDeposit->accrued_interest_amount * 100);
        $originalAccruedInterest = (float) $termDeposit->accrued_interest_amount;
        $accruedInterest = (float) $termDeposit->accrued_interest_amount;
        $lastDueDate = $lastCalculatedAt;

        for ($month = 1; ; $month++) {
            $dueDate = $openedAt->addMonthsNoOverflow($month)->startOfDay();
            if ($dueDate->greaterThan($endDate)) {
                break;
            }
            if ($dueDate->lessThanOrEqualTo($startDate)) {
                continue;
            }

            $dailyBalance = (float) $termDeposit->principal_amount + $accruedInterest;
            $accruedInterest = round(
                $accruedInterest + self::calculateMonthlyInterest($dailyBalance, (float) $termDeposit->annual_interest_rate),
                4
            );
            $lastDueDate = $dueDate;
        }

        if ($lastDueDate->greaterThan($lastCalculatedAt)) {
            $termDeposit->forceFill([
                'accrued_interest_amount' => $accruedInterest,
                'last_interest_calculated_at' => $lastDueDate,
            ])->save();

            $interestCents = (int) round($accruedInterest * 100) - $previousInterestCents;
            if ($interestCents > 0) {
                $this->recordTermOperation(
                    $termDeposit,
                    'daily_interest_capitalization',
                    $interestCents / 100,
                    round((float) $termDeposit->principal_amount + $accruedInterest, 2),
                    'Capitalisation quotidienne des intérêts',
                    (string) Str::uuid(),
                    $actorId
                );
            }
        }

        return round($accruedInterest - $originalAccruedInterest, 4);
    }

    public static function calculateMonthlyInterest(float $balance, float $annualInterestRate): float
    {
        return round($balance * $annualInterestRate / 100 / 12, 4);
    }

    public function calculateInterest(TermDeposit $termDeposit, CarbonImmutable $asOf): float
    {
        $openedAt = CarbonImmutable::parse($termDeposit->opened_at)->startOfDay();
        $maturesAt = CarbonImmutable::parse($termDeposit->matures_at)->startOfDay();
        $calculationDate = $asOf->startOfDay()->min($maturesAt);
        $elapsedDays = max(0, (int) $openedAt->diffInDays($calculationDate));
        $elapsedDays = min($elapsedDays, (int) $termDeposit->term_days);

        return round(
            (float) $termDeposit->principal_amount * (float) $termDeposit->annual_interest_rate / 100 * $elapsedDays / 365,
            2
        );
    }

    private function recordTermOperation(
        TermDeposit $termDeposit,
        string $type,
        float $amount,
        float $balanceAfter,
        string $description,
        string $reference,
        int $actorId
    ): void {
        TermDepositTransaction::create([
            'term_deposit_id' => $termDeposit->id,
            'account_id' => $termDeposit->settlement_account_id,
            'performed_by' => $actorId,
            'type' => $type,
            'reference' => $reference,
            'currency' => $termDeposit->currency,
            'amount' => round($amount, 2),
            'term_balance_after' => round($balanceAfter, 2),
            'description' => $description,
            'occurred_at' => now(),
        ]);
    }

    private function recordAccountOperation(Account $account, int $actorId, string $type, float $amount, string $description): void
    {
        Transaction::create([
            'account_id' => $account->id,
            'user_id' => $actorId,
            'type' => $type,
            'currency' => $account->currency,
            'amount' => round($amount, 2),
            'balance_after' => round((float) $account->balance, 2),
            'description' => $description,
        ]);
    }
}