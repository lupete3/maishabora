<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateLastTransactionDates extends Command
{
    protected $signature = 'members:update-last-transactions';

    protected $description = 'Met à jour la date de la dernière transaction des membres';

    public function handle()
    {
        $this->info('Mise à jour des dernières transactions...');

        DB::statement("
            UPDATE users u
            INNER JOIN (
                SELECT user_id, MAX(created_at) AS last_transaction
                FROM transactions
                GROUP BY user_id
            ) t ON u.id = t.user_id

            SET u.last_transaction_at = t.last_transaction
        ");

        $this->info('Mise à jour terminée.');

        return self::SUCCESS;
    }
}