<?php

use App\Exports\MemberFinancialHistoryExport;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\CreateSubscriptionController;
use App\Http\Controllers\CreditFollowUpReportController;
use App\Http\Controllers\CreditOverviewReportController;
use App\Http\Controllers\CreditReceiptController;
use App\Http\Controllers\CreditReportPdfController;
use App\Http\Controllers\DepositForMemberController;
use App\Http\Controllers\GlobalReportController;
use App\Http\Controllers\GrantCreditController;
use App\Http\Controllers\ManageCashRegisterController;
use App\Http\Controllers\ManageContributionBookController;
use App\Http\Controllers\ManageRepaymentsController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\MemberDetailsController;
use App\Http\Controllers\MemberFinancialHistoryController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RegisterMemberByRecouvreurCOntroller;
use App\Http\Controllers\RegisterMemberController;
use App\Http\Controllers\RepaymentScheduleController;
use App\Http\Controllers\SellMembershipCardController;
use App\Http\Controllers\TransferToCentralCashController;
use App\Livewire\Credit\GrantCredit;
use App\Livewire\Members\CreateMember;
use App\Livewire\Members\SellMembershipCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/caisse-centrale', [ManageCashRegisterController::class, 'index'])->name('cash.register');
});

Route::middleware('auth')->group(function () {
    Route::get('/enregistrer-membre', [RegisterMemberController::class, 'index'])->name('member.register');
});

Route::middleware('auth')->group(function () {
    Route::get('/depot-membre', [DepositForMemberController::class, 'index'])->name('deposit.member');
});

Route::middleware('auth')->group(function () {
    Route::get('/membre/{id}', [MemberDetailsController::class, 'index'])->name('member.details');
});

Route::middleware('auth')->group(function () {
    Route::get('/receipt/transaction/{id}', [ReceiptController::class, 'generate'])->name('receipt.generate');
});

Route::middleware('auth')->group(function () {
    Route::get('/virement-caisse-centrale', [TransferToCentralCashController::class, 'index'])->name('transfer.to.central');
});

Route::middleware('auth')->group(function () {
    Route::get('/receipt/virement/{id}', [TransferToCentralCashController::class, 'generate'])->name('transfer.receipt.generate');
});

Route::middleware('auth')->group(function () {
    Route::get('/tableau-de-bord-agent', [AgentDashboardController::class, 'index'])->name('agent.dashboard');
    Route::get('/agent/transactions/export/{user}/{filter}', [AgentDashboardController::class, 'exportTransactions'])->name('agent.transactions.export');

});

Route::middleware('auth')->group(function () {
    Route::get('/octroyer-credit', [GrantCreditController::class, 'index'])->name('credit.grant');
});
Route::middleware('auth')->group(function () {
    Route::get('/gestion-des-remboursements', [ManageRepaymentsController::class, 'index'])->name('repayments.manage');
});

Route::middleware('auth')->group(function () {
    Route::get('/receipt/credit/{id}', [CreditReceiptController::class, 'generate'])->name('credit.receipt.generate');
});

Route::middleware('auth')->group(function () {
    Route::get('/plan-de-remboursement/{creditId}', [RepaymentScheduleController::class, 'generate'])
        ->name('schedule.generate');
});

Route::middleware('auth')->group(function () {
    Route::get('/rapport-global-crédits', [CreditOverviewReportController::class,'index'])->name('report.credit.overview');
});

Route::get('/export/credits-retard', [CreditReportPdfController::class, 'export'])->name('credits-retard.pdf');
Route::middleware('auth')->group(function () {
    Route::get('/suivi-des-credits', [CreditFollowUpReportController::class, 'index'])->name('report.credit.followup');
});












Route::get('/membres/vendre-carnet', [SellMembershipCardController::class, 'index'])
    ->middleware('auth')
    ->name('members.sell-card');

Route::get('/membres/souscrire', [CreateSubscriptionController::class, 'index'])
    ->middleware('auth')
    ->name('members.subscribe');

Route::get('/membres/carnets', [ManageContributionBookController::class, 'index'])
    ->middleware('auth')
    ->name('members.books');

Route::get('/membre/{id}/dashboard', [MemberDashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin,recouvreur'])
    ->name('member.dashboard');


Route::get('/membre/carnet/{book}/pdf', [ManageContributionBookController::class, 'generatePdf'])
    ->middleware(['auth'])
    ->name('member.book.pdf');

Route::get('/membre/historique', [MemberFinancialHistoryController::class, 'index'])
    ->middleware(['auth'])
    ->name('member.history');

Route::get('/membre/historique/export-excel', function () {
    return Excel::download(new MemberFinancialHistoryExport, 'historique-financier-' . now()->format('Y-m-d') . '.xlsx');
})->name('member.history.excel')->middleware(['auth']);

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/admin/reports/monthly/pdf', [GlobalReportController::class, 'generateMonthlyReport'])
    ->middleware(['auth'])
    ->name('admin.reports.monthly.pdf');

Route::get('/admin/reports/annual/pdf', [GlobalReportController::class, 'generateAnnualReport'])
    ->middleware(['auth'])
    ->name('admin.reports.annual.pdf');

Route::get('/recouvreur/enregistrer-membre', [RegisterMemberByRecouvreurCOntroller::class, 'index'])
    ->middleware(['auth', 'role:admin,recouvreur'])
    ->name('recouvreur.member.register');


Route::get('dashboard', [DashboardController::class,'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::post('/logout', function (): RedirectResponse {
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
