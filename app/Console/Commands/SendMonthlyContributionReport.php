<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CashRegister;
use App\Mail\MonthlyContributionReport;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SendMonthlyContributionReport extends Command
{
    protected $signature = 'reports:monthly-contribution';
    protected $description = 'Envoie un rapport mensuel des contributions à tous les membres';

    public function handle()
    {
        // Récupère tous les membres
        $members = User::where('role', 'membre')->get();

        foreach ($members as $member) {
            // Récupère les dépôts du mois en cours
            $contributions = CashRegister::where('reference_type', 'App\Models\ContributionLine')
                ->whereHasMorph('reference', 'App\Models\ContributionLine', fn($q) => $q->whereHas('contributionBook.subscription', fn($q2) => $q2->where('user_id', $member->id)))
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->get();

            if ($contributions->isNotEmpty()) {
                try {
                    Mail::to($member->email)->send(new MonthlyContributionReport($member, $contributions));
                    Log::info("Rapport mensuel envoyé à : " . $member->email);
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'envoi du rapport à {$member->email} : " . $e->getMessage());
                }
            }
        }

        $this->info('Rapports mensuels envoyés.');
    }
}
