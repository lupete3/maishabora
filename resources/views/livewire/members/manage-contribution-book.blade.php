<div>

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
                                        <h1 class="mb-0 d-inline-block fs-6 lh-1">{{ __("Carnet des membres") }}</h1>
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

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2">

                <h2 class="text-2xl font-bold mb-4">Remplir les carnets de contribution</h2>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <label for="perPage" class="form-label">Afficher par page</label>
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

                @forelse ($books as $book)
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span>Carnet : {{ $book->code }} | Membre : {{ optional($book->subscription->user)->name }}
                                {{ optional($book->subscription->user)->postnom }}
                            </span>
                            @if ($book->subscription->statut === 'actif' && $book->total_amount > 0)
                                <button wire:click="lockBook({{ $book->id }})" class="btn btn-sm btn-danger">Récupérer & Verrouiller</button>
                            @elseif (!$book->verrouille && $book->total_amount == 0)
                                <span class="badge bg-warning">Carte vide</span>
                            @elseif ($book->verrouille)
                                <span class="badge bg-success">Verouillée</span>
                            @endif
                        </div>
                        <div class="card-body pt-2">
                            <h5><strong>Total déposé :</strong> {{ number_format($book->total_amount, 0, ',', '.') }} FC</h5>

                            @if ($book->verrouille)
                                <h5><strong>Total retiré :</strong> {{ number_format($book->total_amount - $book->subscription->montant_souscrit, 0, ',', '.') }} FC</h5>
                            @endif

                            <div class="row g-2">
                                @for ($i = 1; $i <= $book->taille; $i++)
                                    @php
                                        $line = $book->lines->where('numero_ligne', $i)->first();
                                    @endphp
                                    <div class="col-md-2">
                                        <button
                                            type="button"

                                            @if (!$line || $line->montant > 0 || $book->verrouille)
                                                class="btn btn-outline-success text-black w-100 py-2"
                                                disabled
                                            @else
                                                class="btn btn-outline-primary w-100 py-2"
                                                wire:click="fillLine({{ $book->id }}, {{ $i }})"
                                            @endif
                                        >
                                            Jour {{ $i }}
                                            @if ($line && $line->montant)
                                                <br><small>{{ number_format($line->montant, 0, ',', '.') }} FC</small>
                                            @endif
                                        </button>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                @empty

                    <div class="alert alert-danger" role="alert">{{ __('Aucune information disponible pour le moment') }}</div>

                @endforelse

                {{ $books->links() }}
            </div>
    </div>

    </div>




<!-- Modal pour remplir une ligne -->
<div class="modal fade" id="modalFillLine" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="modalTitleId" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                 <form wire:submit.prevent="saveLine">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitleId">Remplir la ligne {{ $lineNumber }}</h5>
                    <button type="button" class="btn-close" wire:click="$dispatch('closeModal', { name: 'modalFillLine' })" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Montant (FC)</label>
                        <input type="number" wire:model="amount" id="amount" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$dispatch('closeModal', { name: 'modalFillLine' })">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading wire:target="saveLine" class="spinner-border spinner-border-sm me-2"
                            role="status"></span>
                        Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
