<div class="">

    <div class="page-wrapper">

        <div class="page-header d-print-none">
                <div class="">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <div class="page-pretitle">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a class="mb-0 d-inline-block fs-6 lh-1" href="{{ route('dashboard') }}">{{
                                                __("Tableau de bord") }}</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            <h1 class="mb-0 d-inline-block fs-6 lh-1">{{ __("Souscription des membres") }}</h1>
                                        </li>
                                    </ol>
                                </nav>

                            </div>
                        </div>
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <div class="page-body page-content">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-4">

                    <h2 class="text-2xl font-bold mb-4">{{ __('Nouvelle souscription') }}</h2>

                    <form wire:submit.prevent="submit">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">{{ __('Membre') }}</label>
                            <select wire:model="user_id" id="user_id" class="form-select select2" required>
                                <option value="" disabled>{{ __('-- Sélectionner --') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} {{ $user->postnom }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="montant_souscrit" class="form-label">{{ __('Montant souscrit (FC)') }}</label>
                            <input type="number" wire:model="montant_souscrit" id="montant_souscrit" class="form-control" placeholder="Ex: 10000" required>
                            @error('montant_souscrit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm me-2"
                            role="status"></span>
                            {{ __('Créer la souscription') }}</button>
                    </form>

                    <hr class="my-4">

                <h3 class="text-lg font-semibold mb-3">{{ __('Liste des souscriptions') }}</h3>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label for="perPage" class="form-label">{{ __('Afficher par page') }}</label>
                            <select wire:model.live="perPage" id="perPage" class="form-select form-select-sm w-auto">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div class="w-25">
                            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher...">
                        </div>
                    </div>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Membre') }}</th>
                                <th>{{ __('Montant souscrit') }}</th>
                                <th>{{ __('Date création') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subscriptions as $subscription)
                                <tr>
                                    <td>{{ $subscription->user->name }} {{ $subscription->user->postnom }}</td>
                                    <td>{{ number_format($subscription->montant_souscrit, 0, ',', '.') }} FC</td>
                                    <td>{{ $subscription->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center"><div class="alert alert-danger" role="alert">{{ __('Aucune information disponible pour le moment') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{ $subscriptions->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

@script
<script>
        $(document).ready(function() {
            $('.select2').on('change', function (e) {
                const data = $(this).val();
                @this.set('user_id', data); // synchronise avec la propriété Livewire
            });
        });

    </script>
@endscript
