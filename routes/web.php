<?php

use App\Exports\MemberFinancialHistoryExport;
use App\Helpers\UserLogHelper;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\AgentTransactionsReportController;
use App\Http\Controllers\ClientStatReportController;
use App\Http\Controllers\ClotureController;
use App\Http\Controllers\CreateSubscriptionController;
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
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RegisterMemberByRecouvreurCOntroller;
use App\Http\Controllers\RegisterMemberController;
use App\Http\Controllers\RepaymentScheduleController;
use App\Http\Controllers\RepaymentReportController;
use App\Http\Controllers\TransferToCentralCashController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;


Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth','auth.session','permission:afficher-role|afficher-utilisateur'])->group( function () {
    Route::get('/users', [UserController::class, 'index'])->name('user.management');
    Route::get('/roles', [UserController::class, 'roles'])->name('role.management');
});

Route::middleware(['auth','auth.session','permission:afficher-caisse-centrale'])->group(function () {
    Route::get('/caisse-centrale', [ManageCashRegisterController::class, 'index'])->name('cash.register');
    Route::get('/caisse-centrale/export-transactions', [ManageCashRegisterController::class, 'generate'])
        ->name('cash.register.export.pdf');
});


Route::middleware(['auth','auth.session','permission:afficher-client'])->group(function () {
    Route::get('/enregistrer-membre', [RegisterMemberController::class, 'index'])->name('member.register');
    Route::get('/membre/{id}', [MemberDetailsController::class, 'index'])->name('member.details');
    Route::get('/membre/{id}/export-transactions', [MemberTransactionReportController::class, 'generate'])
        ->name('member.transactions.export');
    Route::get('/receipt/transaction/{id}', [ReceiptController::class, 'generate'])->name('receipt.generate');
    Route::get('/receipt/transactionpos/{id}', [ReceiptController::class, 'generatePos'])->name('receipt.generate_pos');

});

Route::middleware(['auth','auth.session'])->group(function () {
    Route::get('/membre/{id}/export-transactions', [MemberTransactionReportController::class, 'generate'])
        ->name('member.transactions.export');
    Route::get('/membre/{id}/fiche-client', [MemberTransactionReportController::class, 'print'])
        ->name('member.print');
});

Route::middleware(['auth','auth.session','permission:depot-compte-membre'])->group(function () {
    Route::get('/depot-membre', [DepositForMemberController::class, 'index'])->name('deposit.member');
});

Route::middleware(['auth','auth.session','permission:afficher-transfert-caisse'])->group(function () {
    Route::get('/virement-caisse-centrale', [TransferToCentralCashController::class, 'index'])->name('transfer.to.central');
    Route::get('/receipt/virement/{id}', [TransferToCentralCashController::class, 'generate'])->name('transfer.receipt.generate');
});

Route::middleware(['auth','auth.session','permission:afficher-caisse-agent'])->group(function () {
    Route::get('/tableau-de-bord-agent', [AgentDashboardController::class, 'index'])->name('agent.dashboard');
});

Route::middleware(['auth','auth.session'])->group(function () {
    Route::get('/agent/transactions/export/{user}/{filter}', [AgentDashboardController::class, 'exportTransactions'])->name('agent.transactions.export');
});

Route::middleware(['auth','auth.session','permission:ajouter-credit'])->group(function () {
    Route::get('/octroyer-credit', [GrantCreditController::class, 'index'])->name('credit.grant');
    Route::get('/receipt/credit/{id}', [CreditReceiptController::class, 'generate'])->name('credit.receipt.generate');
});

Route::middleware(['auth','auth.session','permission:afficher-credit'])->group(function () {
    Route::get('/gestion-des-remboursements', [ManageRepaymentsController::class, 'index'])->name('repayments.manage');
    Route::get('/plan-de-remboursement/{creditId}', [RepaymentScheduleController::class, 'generate'])
        ->name('schedule.generate');
    Route::get('/rapport-global-crédits', [CreditOverviewReportController::class,'index'])->name('report.credit.overview');
    Route::get('/export/credits-retard', [CreditReportPdfController::class, 'export'])->name('credits-retard.pdf');
    Route::get('/suivi-des-credits', [CreditFollowUpReportController::class, 'index'])->name('report.credit.followup');
    Route::get('/rapport-remboursements', [RepaymentReportController::class, 'index'])->name('report.repayments');
    Route::get('/comptes-membres', [MemberDetailsController::class, 'comptes'])->name('member.accounts');

});

Route::middleware(['auth','auth.session','permission:afficher-simulation-credit'])->group(function () {
    Route::get('/smulation-credit', [RepaymentScheduleController::class, 'simulation'])->name('repayments.simulation');
});

Route::middleware(['auth','auth.session','permission:afficher-carnet'])->group(function () {
    Route::get('/membres/vendre-carnet', [MembershipCardController::class, 'index'])->name('members.sell-card');
});

Route::middleware(['auth','auth.session','permission:depot-compte-membre'])->group(function () {
    Route::get('/membres/depot-carnet', [MembershipCardController::class, 'depot'])->name('members.deposit-card');
});

Route::middleware(['auth','auth.session','permission:retrait-compte-membre'])->group(function () {
    Route::get('/membres/retrait-carnet', [MembershipCardController::class, 'withdrawfromcard'])->name('members.withdrawfrom-card');
});

Route::middleware(['auth','auth.session','permission:depot-compte-membre'])->group(function () {
    Route::get('/cloture-caisse', [ClotureController::class, 'index'])->name('agent.cloture');
    Route::get('/cloture-impression/{id}', [ClotureController::class, 'exportFiche'])->name('cloture.print');
});

Route::middleware(['auth','auth.session','permission:effectuer-virement'])->group(function () {
    Route::get('/transfert-compte', [FundTransferController::class, 'index'])->name('transfert.ajouter');
});


Route::middleware(['auth','auth.session','permission:afficher-rapport-client|afficher-rapport-carnet'])->group(function () {
    Route::get('/rapport-client', [ClientStatReportController::class, 'rapportClient'])->name('rapports.clients');
    Route::get('/rapport-carnets', [ClientStatReportController::class, 'rapportCarnets'])->name('rapports.carnets');
    Route::get('/rapport-transactions', [AgentTransactionsReportController::class, 'rapportTransactions'])->name('rapports.transactions');
    Route::get('/rapport-depot-retrait', [AgentTransactionsReportController::class, 'rapportDepotRetrait'])->name('rapports.depot_retrait');
});








Route::get('/membres/souscrire', [CreateSubscriptionController::class, 'index'])
    ->middleware(['auth','auth.session'])
    ->name('members.subscribe');

Route::get('/membres/carnets', [ManageContributionBookController::class, 'index'])
    ->middleware(['auth','auth.session'])
    ->name('members.books');

Route::get('/membre/{id}/dashboard', [MemberDashboardController::class, 'index'])
    ->middleware(['auth','auth.session', 'role:admin,recouvreur'])
    ->name('member.dashboard');


Route::get('/membre/carnet/{book}/pdf', [ManageContributionBookController::class, 'generatePdf'])
    ->middleware(['auth','auth.session'])
    ->name('member.book.pdf');

Route::get('/membre/historique', [MemberFinancialHistoryController::class, 'index'])
    ->middleware(['auth','auth.session'])
    ->name('member.history');

Route::get('/membre/historique/export-excel', function () {
    return Excel::download(new MemberFinancialHistoryExport, 'historique-financier-' . now()->format('Y-m-d') . '.xlsx');
})->name('member.history.excel')->middleware(['auth','auth.session']);

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth','auth.session', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/admin/reports/monthly/pdf', [GlobalReportController::class, 'generateMonthlyReport'])
    ->middleware(['auth','auth.session'])
    ->name('admin.reports.monthly.pdf');

Route::get('/admin/reports/annual/pdf', [GlobalReportController::class, 'generateAnnualReport'])
    ->middleware(['auth','auth.session'])
    ->name('admin.reports.annual.pdf');

Route::get('/recouvreur/enregistrer-membre', [RegisterMemberByRecouvreurCOntroller::class, 'index'])
    ->middleware(['auth','auth.session', 'role:admin,recouvreur'])
    ->name('recouvreur.member.register');


Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth','auth.session', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth','auth.session'])
    ->name('profile');

Route::post('/logout', function () {
    UserLogHelper::log_user_activity('Déconnexion', 'Utilisateur déconnecté');
    
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect('/login');
})->name('logout');

//Route to 404 page not found
Route::fallback(function(){
    return view('not-found');
});

require __DIR__.'/auth.php';
