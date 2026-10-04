@extends('layouts.backend')

@section('title', 'Ouvrir un dépôt à terme')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Ouvrir un dépôt à terme</h4>
            <a href="{{ route('term-deposits.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Retour</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('term-deposits.store') }}" class="card">
            @csrf
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="term_deposit_product_id">Produit</label>
                    <select class="form-select" id="term_deposit_product_id" name="term_deposit_product_id" required>
                        <option value="">Sélectionner un produit</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('term_deposit_product_id') == $product->id)>
                                {{ $product->name }} · {{ $product->term_months ? $product->term_months . ' mois' : $product->term_days . ' jours' }} · {{ number_format((float) $product->annual_interest_rate, 4, ',', ' ') }} % annuel
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="member_search">Rechercher un membre</label>
                    <input class="form-control mb-2" id="member_search" type="search" placeholder="Nom, code ou téléphone" autocomplete="off">
                    <label class="form-label" for="member_id">Membre</label>
                    <select class="form-select" id="member_id" name="member_id" required>
                        <option value="">Sélectionner un membre</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" data-search="{{ strtolower(trim($member->name . ' ' . $member->postnom . ' ' . $member->prenom . ' ' . $member->code . ' ' . $member->telephone)) }}" @selected(old('member_id') == $member->id)>
                                {{ trim($member->name . ' ' . $member->postnom . ' ' . $member->prenom) }} · {{ $member->code }} · {{ $member->telephone }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="source_account_id">Compte source</label>
                    <select class="form-select" id="source_account_id" name="source_account_id" required>
                        <option value="">Choisir d’abord un membre</option>
                        @foreach ($members as $member)
                            @foreach ($member->accounts as $account)
                                <option value="{{ $account->id }}" data-member-id="{{ $member->id }}" @selected(old('source_account_id') == $account->id)>
                                    {{ ucfirst($account->type) }} · {{ $account->currency }} · solde {{ number_format((float) $account->balance, 2, ',', ' ') }}
                                </option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="amount">Capital à placer</label>
                    <input class="form-control" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                </div>
                <div class="col-12">
                    <div class="alert alert-info mb-0">Le membre et la devise sont déterminés par le compte source. Les conditions du produit seront copiées sur le contrat et ne changeront plus après l’ouverture.</div>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-primary" type="submit" @disabled($products->isEmpty() || $members->isEmpty())>Confirmer l’ouverture</button>
                </div>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const memberSearch = document.getElementById('member_search');
            const memberSelect = document.getElementById('member_id');
            const accountSelect = document.getElementById('source_account_id');
            const accountOptions = Array.from(accountSelect.querySelectorAll('option[data-member-id]'));

            memberSearch.addEventListener('input', () => {
                const query = memberSearch.value.trim().toLocaleLowerCase();
                Array.from(memberSelect.options).forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = query !== '' && !option.dataset.search.includes(query);
                });
                if (memberSelect.selectedOptions[0]?.hidden) {
                    memberSelect.value = '';
                    memberSelect.dispatchEvent(new Event('change'));
                }
            });

            memberSelect.addEventListener('change', () => {
                const memberId = memberSelect.value;
                accountSelect.value = '';
                accountOptions.forEach((option) => {
                    option.hidden = !memberId || option.dataset.memberId !== memberId;
                });
                accountSelect.options[0].textContent = memberId ? 'Sélectionner un compte actif' : 'Choisir d’abord un membre';
                accountSelect.disabled = !memberId;
            });

            memberSelect.dispatchEvent(new Event('change'));
        });
    </script>
@endsection