<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Account;

class MigrateMemberAccounts extends Command
{
    protected $signature = 'microfinance:migrate-accounts {--dry-run}';
    protected $description = 'Migration des comptes legacy vers current / savings';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $members = User::where('role','membre')->get();
        $this->info('Membres trouvés : ' . $members->count());

        foreach ($members as $user) {
            DB::transaction(function () use ($user, $dryRun) {

                foreach (['USD', 'CDF'] as $currency) {

                    $current = Account::where([
                        'user_id' => $user->id,
                        'currency' => $currency,
                        'type' => 'current'
                    ])->lockForUpdate()->first();

                    if (!$current) {
                        $legacy = Account::where('user_id', $user->id)
                            ->where('currency', $currency)
                            ->where(function ($q) {
                                $q->whereNull('type')->orWhere('type', 'normal');
                            })
                            ->lockForUpdate()
                            ->first();

                        if ($legacy) {
                            if (!$dryRun) {
                                $legacy->update(['type' => 'current']);
                            }
                            $this->line("✔ {$user->code} {$currency} legacy → current");
                        } else {
                            if (!$dryRun) {
                                Account::create([
                                    'user_id' => $user->id,
                                    'currency' => $currency,
                                    'type' => 'current',
                                    'balance' => 0
                                ]);
                            }
                            $this->line("➕ {$user->code} {$currency} current créé");
                        }
                    }

                    Account::firstOrCreate([
                        'user_id' => $user->id,
                        'currency' => $currency,
                        'type' => 'savings'
                    ], ['balance' => 0]);
                }
            });
        }

        $this->info($dryRun
            ? 'Simulation terminée (aucune donnée modifiée).'
            : 'Migration terminée avec succès.');
    }
}
