<?php

use App\Exports\MemberFinancialHistoryExport;
use App\Helpers\UserLogHelper;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\AgentTransactionsReportController;
use App\Http\Controllers\ClientStatReportController;
use App\Http\Controllers\ClotureController;
use App\Http\Controllers\ComptabiliteController;
use App\Http\Controllers\CreateSubscriptionController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\CreditFollowUpReportController;
use App\Http\Controllers\CreditOverviewReportController;
use App\Http\Controllers\CreditReceiptController;
use App\Http\Controllers\CreditReportPdfController;
use App\Http\Controllers\DepositForMemberController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\GlobalReportController;
use App\Http\Controllers\GrantCreditController;
use App\Http\Controllers\ManageCashRegisterController;
use App\Http\Controllers\ManageContributionBookController;
use App\Http\Controllers\ManageRepaymentsController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\MemberDetailsController;
use App\Http\Controllers\MemberFinancialHistoryController;
use App\Http\Controllers\MembershipCardController;
use App\Http\Controllers\MemberTransactionReportController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RegisterMemberByRecouvreurCOntroller;
use App\Http\Controllers\RegisterMemberController;
use App\Http\Controllers\RepaymentScheduleController;
use App\Http\Controllers\RepaymentReportController;
use App\Http\Controllers\ReportAIController;
use App\Http\Controllers\TransferToCentralCashController;
use App\Http\Controllers\UserController;
use App\Http\Livewire\Credit\LoanApplicationCreate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-role|afficher-utilisateur'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('user.management');
    Route::get('/roles', [UserController::class, 'roles'])->name('role.management');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-informations-entreprise'])->group(function () {
    Route::get('/entreprise', [\App\Http\Controllers\Admin\CompanyInformationController::class, 'index'])->name('company.information');
    Route::post('/entreprise/update', [\App\Http\Controllers\Admin\CompanyInformationController::class, 'update'])->name('company.information.update');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-caisse-centrale'])->group(function () {
    Route::get('/caisse-centrale', [ManageCashRegisterController::class, 'index'])->name('cash.register');
    Route::get('/caisse-centrale/export-transactions', [ManageCashRegisterController::class, 'generate'])
        ->name('cash.register.export.pdf');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-client'])->group(function () {
    Route::get('/enregistrer-membre', [RegisterMemberController::class, 'index'])->name('member.register');
    Route::get('/membre/{id}', [MemberDetailsController::class, 'index'])->name('member.details');
    Route::get('/membre/{id}/export-transactions', [MemberTransactionReportController::class, 'generate'])
        ->name('member.transactions.export');
    Route::get('/receipt/transaction/{id}', [ReceiptController::class, 'generate'])->name('receipt.generate');
    Route::get('/receipt/transactionpos/{id}', [ReceiptController::class, 'generatePos'])->name('receipt.generate_pos');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/membre/{id}/export-transactions', [MemberTransactionReportController::class, 'generate'])
        ->name('member.transactions.export');
    Route::get('/membre/{id}/fiche-client', [MemberTransactionReportController::class, 'print'])
        ->name('member.print');
});

Route::middleware(['auth', 'auth.session', 'permission:depot-compte-membre'])->group(function () {
    Route::get('/depot-membre', [DepositForMemberController::class, 'index'])->name('deposit.member');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-transfert-caisse'])->group(function () {
    Route::get('/virement-caisse-centrale', [TransferToCentralCashController::class, 'index'])->name('transfer.to.central');
    Route::get('/receipt/virement/{id}', [TransferToCentralCashController::class, 'generate'])->name('transfer.receipt.generate');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-caisse-agent'])->group(function () {
    Route::get('/tableau-de-bord-agent', [AgentDashboardController::class, 'index'])->name('agent.dashboard');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/agent/{userId}/transactions/export/{filter?}', [AgentDashboardController::class, 'exportTransactions'])
        ->name('agent.transactions.export');
});

Route::middleware(['auth', 'auth.session', 'permission:ajouter-credit'])->group(function () {
    Route::get('/octroyer-credit', [GrantCreditController::class, 'index'])->name('credit.grant');
    Route::get('/receipt/credit/{id}', [CreditReceiptController::class, 'generate'])->name('credit.receipt.generate');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-credit'])->group(function () {
    Route::get('/gestion-des-remboursements', [ManageRepaymentsController::class, 'index'])->name('repayments.manage');
    Route::get('/plan-de-remboursement/{creditId}', [RepaymentScheduleController::class, 'generate'])
        ->name('schedule.generate');
    Route::get('/rapport-global-crédits', [CreditOverviewReportController::class, 'index'])->name('report.credit.overview');
    Route::get('/export/credits-retard', [CreditReportPdfController::class, 'export'])->name('credits-retard.pdf');
    Route::get('/export/credits-retard-csv', [CreditOverviewReportController::class, 'exportCsv'])->name('credits-retard.csv');
    Route::get('/suivi-des-credits', [CreditFollowUpReportController::class, 'index'])->name('report.credit.followup');
    Route::get('/rapport-remboursements', [RepaymentReportController::class, 'index'])->name('report.repayments');
    Route::get('/comptes-membres', [MemberDetailsController::class, 'comptes'])->name('member.accounts');

});

Route::middleware(['auth', 'auth.session', 'permission:afficher-simulation-credit'])->group(function () {
    Route::get('/smulation-credit', [RepaymentScheduleController::class, 'simulation'])->name('repayments.simulation');
});

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/credit/applications', [CreditController::class, 'index'])->name('credit.applications.list');
    Route::get('/credit/applications/create', [CreditController::class, 'create'])->name('credit.applications.create');
    Route::get('/credit/applications/print-blank', [App\Http\Controllers\CreditPrintController::class, 'printBlank'])->name('credit.applications.print-blank');
    Route::get('/credit/applications/{id}/print-filled', [App\Http\Controllers\CreditPrintController::class, 'printFilled'])->name('credit.applications.print-filled');
    Route::get('/credit/applications/{id}', function ($id) {
        $loan = \App\Models\LoanApplication::with(['user', 'business', 'ratios', 'cashflow', 'balance', 'securities'])->findOrFail($id);
        return view('credit.applications.show', compact('loan'));
    })->name('credit.applications.show');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-carnet'])->group(function () {
    Route::get('/membres/vendre-carnet', [MembershipCardController::class, 'index'])->name('members.sell-card');
});

Route::middleware(['auth', 'auth.session', 'permission:depot-compte-membre'])->group(function () {
    Route::get('/membres/depot-carnet', [MembershipCardController::class, 'depot'])->name('members.deposit-card');
});

Route::middleware(['auth', 'auth.session', 'permission:retrait-compte-membre'])->group(function () {
    Route::get('/membres/retrait-carnet', [MembershipCardController::class, 'withdrawfromcard'])->name('members.withdrawfrom-card');
});

Route::middleware(['auth', 'auth.session', 'permission:decaissement'])->group(function () {
    Route::get('/gestion-decaissements', [App\Http\Controllers\DisbursementController::class, 'index'])->name('disbursement.index');
});

Route::middleware(['auth', 'auth.session', 'permission:approuver-decaissement'])->group(function () {
    Route::get('/approbation-decaissements', [App\Http\Controllers\DisbursementController::class, 'approval'])->name('disbursement.approval');
});

Route::middleware(['auth', 'auth.session', 'permission:depot-compte-membre'])->group(function () {
    Route::get('/cloture-caisse', [ClotureController::class, 'index'])->name('agent.cloture');
    Route::get('/cloture-impression/{id}', [ClotureController::class, 'exportFiche'])->name('cloture.print');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-caisse-agent'])->group(function () {
    Route::get('/ecarts-caisse', [App\Http\Controllers\EcartCaisseController::class, 'index'])->name('ecarts.caisse');
    Route::get('/ecarts-caisse-export', [App\Http\Controllers\EcartCaisseController::class, 'exportPdf'])->name('ecarts.export');
    Route::get('/rapport-performance-agents', [App\Http\Controllers\AgentReportController::class, 'index'])->name('reports.agent-performance');
    Route::get('/rapport-performance-agents-export', [App\Http\Controllers\AgentReportController::class, 'export'])->name('reports.agent-performance.export');
});

Route::middleware(['auth', 'auth.session', 'permission:effectuer-virement'])->group(function () {
    Route::get('/transfert-compte', [FundTransferController::class, 'index'])->name('transfert.ajouter');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-paye'])->group(function () {
    Route::get('/paie-salarie', [PayrollController::class, 'index'])->name('payroll.index');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-rapport-comptable'])->group(function () {
    Route::get('/comptes-comptabilite', [ComptabiliteController::class, 'index'])->name('comptabilite.comptes');
    Route::get('/type-journal', [ComptabiliteController::class, 'typeJournal'])->name('comptabilite.type_journal');
    Route::get('/journals', [ComptabiliteController::class, 'journals'])->name('comptabilite.journals');
    Route::get('/grand-livre', [ComptabiliteController::class, 'grandLivre'])->name('comptabilite.grand_livre');
    Route::get('/balances', [ComptabiliteController::class, 'balanceGenerale'])->name('comptabilite.balance');
    Route::get('/compte-resultat', [ComptabiliteController::class, 'compteResultat'])->name('comptabilite.compte_resultat');
    Route::get('/bilan', [ComptabiliteController::class, 'bilan'])->name('comptabilite.bilan');
    Route::get('/provisions', [ComptabiliteController::class, 'provisions'])->name('comptabilite.provisions');
    Route::get('/export/provisions', [App\Http\Controllers\ProvisionReportController::class, 'export'])->name('provisions.export.pdf');
    Route::get('/resultats', [ComptabiliteController::class, 'resultats'])->name('comptabilite.resultats');
    Route::get('/ratios-gestion', function () {
        return view('reports.management-ratios');
    })->name('comptabilite.ratios');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-rapport-client|afficher-rapport-carnet'])->group(function () {
    Route::get('/rapport-client', [ClientStatReportController::class, 'rapportClient'])->name('rapports.clients');
    Route::get('/rapport-compte-clients-pdf', [ClientStatReportController::class, 'compteClientsPdf'])->name('rapports.compte-clients.pdf');
    Route::get('/rapport-compte-clients-excel', [ClientStatReportController::class, 'compteClientsExcel'])->name('rapports.compte-clients.excel');
    Route::get('/rapport-carnets', [ClientStatReportController::class, 'rapportCarnets'])->name('rapports.carnets');
    Route::get('/membres/overview-carnets', function () {
        return view('members.carnet-overview');
    })->name('members.carnet-overview');
    Route::get('/membres/overview-carnets/export-pdf', [ClientStatReportController::class, 'carnetOverviewPdf'])->name('members.overview-carnets.pdf');
    Route::get('/rapport-transactions', [AgentTransactionsReportController::class, 'rapportTransactions'])->name('rapports.transactions');
    Route::get('/rapport-depot-retrait', [AgentTransactionsReportController::class, 'rapportDepotRetrait'])->name('rapports.depot_retrait');
});

Route::middleware(['auth', 'auth.session', 'permission:afficher-logs'])->group(function () {
    Route::get('/ai/reports/daily', [ReportAIController::class, 'index'])->name('ai.reports');
    Route::get('/ai/reports/credit', function () {
        return view('reports.ai-credit');
    })->name('ai.reports.credit');
    Route::get('/ai/reports/clients', function () {
        return view('reports.ai-clients');
    })->name('ai.reports.clients');
    Route::get('/ai/reports/sales', function () {
        return view('reports.ai-sales');
    })->name('ai.reports.sales');
    Route::get('/rapport-logs', [DashboardController::class, 'rapportLogs'])->name('rapports.logs');
    Route::get('/system/run-cron', function() {
        \Illuminate\Support\Facades\Artisan::call('check:overdue-repayments');
        return back()->with('success', 'La vérification des retards a été exécutée manuellement avec succès.');
    })->name('system.run-cron');
});

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'auth.session', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'auth.session'])
    ->name('profile');

Route::get('/notifications', [DashboardController::class, 'notifications'])
    ->middleware(['auth', 'auth.session'])
    ->name('notifications.index');

Route::post('/logout', function () {
    UserLogHelper::log_user_activity('Déconnexion', 'Utilisateur déconnecté');

    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');


// Web Cron Route for scheduled tasks (bypasses hosting cron limits)
Route::get('/cron/executer-retards/{token}', function ($token) {
    $secretToken = 'maishabora_cron_secret_7x9A2mP5vK3';
    if ($token !== $secretToken) {
        abort(403, 'Accès refusé');
    }
    \Illuminate\Support\Facades\Artisan::call('check:overdue-repayments');
    return 'Vérification des retards exécutée avec succès à ' . now();
});

//Route to 404 page not found
Route::fallback(function () {
    return view('not-found');
});

require __DIR__ . '/auth.php';
