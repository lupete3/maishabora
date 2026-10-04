<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TermDeposit;
use App\Models\TermDepositProduct;
use App\Models\User;
use App\Services\TermDepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class TermDepositController extends Controller
{
    public function index(Request $request): View
    {
        $products = TermDepositProduct::query()->orderBy('name')->get();
        $search = trim((string) $request->query('search', ''));

        $termDeposits = TermDeposit::query()->with(['user', 'product'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', ctype_digit($search) ? (int) $search : -1)
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('postnom', 'like', '%' . $search . '%')
                                ->orWhere('prenom', 'like', '%' . $search . '%')
                                ->orWhere('code', 'like', '%' . $search . '%')
                                ->orWhere('telephone', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest('id')->paginate(25)->withQueryString();

        return view('term-deposits.index', compact('products', 'termDeposits', 'search'));
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:term_deposit_products,name'],
            'term_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'annual_interest_rate' => ['required', 'numeric', 'min:0', 'max:1000'],
            'early_withdrawal_penalty_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'early_withdrawal_interest_policy' => ['required', Rule::in(['forfeit', 'pro_rata'])],
            'minimum_amount' => ['required', 'numeric', 'gt:0'],
            'maximum_amount' => ['nullable', 'numeric', 'gte:minimum_amount'],
        ]);

        $termMonths = (int) $validated['term_months'];
        unset($validated['term_months']);
        TermDepositProduct::create($validated + [
            'term_months' => $termMonths,
            'term_days' => $termMonths * 30,
            'is_active' => true,
        ]);

        notyf()->success('Produit de dépôt à terme créé.');
        return back();
    }

    public function toggleProduct(TermDepositProduct $product): RedirectResponse
    {
        $product->is_active = !$product->is_active;
        $product->save();

        notyf()->success($product->is_active ? 'Produit activé.' : 'Produit désactivé.');
        return back();
    }

    public function create(): View
    {
        $products = TermDepositProduct::query()->where('is_active', true)->orderBy('name')->get();

        $members = User::query()->where('role', 'membre')->where('status', true)
            ->whereHas('accounts', fn ($query) => $query->whereIn('type', ['current', 'savings'])
                ->where('status', 'Actif')->whereIn('currency', ['USD', 'CDF']))
            ->with(['accounts' => fn ($query) => $query->whereIn('type', ['current', 'savings'])
                ->where('status', 'Actif')->whereIn('currency', ['USD', 'CDF'])
                ->orderBy('currency')->orderBy('type')])
            ->orderBy('name')->orderBy('postnom')->orderBy('prenom')->get();

        return view('term-deposits.create', compact('products', 'members'));
    }

    public function store(Request $request, TermDepositService $termDepositService): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['required', 'exists:users,id'],
            'term_deposit_product_id' => ['required', 'exists:term_deposit_products,id'],
            'source_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $request->input('member_id'))),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $sourceAccount = Account::query()->findOrFail($validated['source_account_id']);
        $member = User::query()->findOrFail($validated['member_id']);

        $termDepositService->open(
            $member,
            (int) $validated['term_deposit_product_id'],
            (int) $validated['source_account_id'],
            (float) $validated['amount'],
            $request->user()->id
        );

        notyf()->success('Dépôt à terme ouvert avec succès.');
        return redirect()->route('term-deposits.index');
    }

    public function memberHistory(User $user, TermDeposit $termDeposit): View
    {
        abort_unless((int) $termDeposit->user_id === (int) $user->id, 404);

        $termDeposit->load(['product', 'sourceAccount', 'settlementAccount']);
        $transactions = $termDeposit->transactions()->with('performedBy')
            ->orderByDesc('occurred_at')->orderByDesc('id')->paginate(25);

        return view('term-deposits.member-history', compact('user', 'termDeposit', 'transactions'));
    }

    public function withdrawEarly(TermDeposit $termDeposit, TermDepositService $termDepositService): RedirectResponse
    {
        $termDepositService->withdrawEarly($termDeposit->id, request()->user()->id);

        notyf()->success('Le dépôt à terme a été clôturé et réglé selon les conditions de retrait anticipé.');
        return back();
    }

    public function settleAtMaturity(TermDeposit $termDeposit, TermDepositService $termDepositService): RedirectResponse
    {
        abort_unless($termDepositService->settleAtMaturity($termDeposit->id, request()->user()->id), 422);

        notyf()->success('Le capital et les intérêts capitalisés ont été versés sur le compte courant du membre.');
        return back();
    }
}