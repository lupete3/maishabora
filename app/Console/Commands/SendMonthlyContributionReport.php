<?php

namespace App\Console\Commands;

use App\Models\DailyContribution;
use App\Models\User;
use App\Mail\MonthlyContributionReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

class SendMonthlyContributionReport extends Command
{
    protected $signature = 'reports:monthly-contribution';
    protected $description = 'Envoie un rapport mensuel des contributions à tous les membres';

    public function handle()
    {
        $this->info("Démarrage de l'envoi des rapports mensuels...");

        // Récupère tous les membres qui ont des cartes de membres (carnets)
        $members = User::where('role', 'membre')
            ->whereHas('membershipCards')
            ->get();

        $count = 0;
        foreach ($members as $member) {
            // Récupère les contributions payées du mois en cours pour ce membre
            $contributions = DailyContribution::where('is_paid', true)
                ->whereMonth('contribution_date', now()->month)
                ->whereYear('contribution_date', now()->year)
                ->whereHas('card', function ($query) use ($member) {
                    $query->where('member_id', $member->id);
                })
                ->with('card') // Eager load pour le PDF
                ->get();

            if ($contributions->isNotEmpty()) {
                try {
                    if (empty($member->email)) {
                        Log::warning("Impossible d'envoyer le rapport à {$member->name} : Email manquant.");
                        continue;
                    }

                    Mail::to($member->email)->send(new MonthlyContributionReport($member, $contributions));
                    Log::info("Rapport mensuel envoyé à : " . $member->email);
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'envoi du rapport à {$member->email} : " . $e->getMessage());
                    $this->error("Erreur pour {$member->email} : " . $e->getMessage());
                }
            }
        }

        $this->info("Traitement terminé. {$count} rapports envoyés.");
    }
}
