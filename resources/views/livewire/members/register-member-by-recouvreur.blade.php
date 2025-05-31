<div>
    <div class="card mb-4">
        <h5 class="card-header text-lg font-medium text-gray-900">Enregistrer un nouveau membre</h5>

        <div class="card-body">
            <p class="text-muted mb-4">
                Compléter toutes les zones de texte recquises avant d'enregistrer.
            </p>

            <form wire:submit.prevent="register" class="row g-3">

                <!-- Nom -->
                <div class="col-md-4">
                    <label for="name" class="form-label">Nom</label>
                    <input wire:model.defer="name" id="name" type="text" class="form-control">
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Postnom -->
                <div class="col-md-4">
                    <label for="postnom" class="form-label">Postnom</label>
                    <input wire:model.defer="postnom" id="postnom" type="text" class="form-control">
                    @error('postnom') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Prénom -->
                <div class="col-md-4">
                    <label for="prenom" class="form-label">Prénom (optionnel)</label>
                    <input wire:model.defer="prenom" id="prenom" type="text" class="form-control">
                    @error('prenom') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Date de naissance -->
                <div class="col-md-4">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input wire:model.defer="date_naissance" id="date_naissance" type="date" class="form-control">
                    @error('date_naissance') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Téléphone -->
                <div class="col-md-4">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input wire:model.defer="telephone" id="telephone" type="text" class="form-control"
                        placeholder="+243999999999">
                    @error('telephone') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Email -->
                <div class="col-md-4">
                    <label for="email" class="form-label">Email</label>
                    <input wire:model.defer="email" id="email" type="email" class="form-control">
                    @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Adresse physique -->
                <div class="col-md-6">
                    <label for="adresse_physique" class="form-label">Adresse physique</label>
                    <textarea wire:model.defer="adresse_physique" id="adresse_physique" rows="2"
                        class="form-control"></textarea>
                    @error('adresse_physique') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Profession -->
                <div class="col-md-6">
                    <label for="profession" class="form-label">Profession</label>
                    <input wire:model.defer="profession" id="profession" type="text" class="form-control">
                    @error('profession') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <!-- Bouton soumission -->
                <div class="col-md-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <span wire:loading wire:target="register" class="spinner-border spinner-border-sm me-2"
                            role="status"></span>
                        Enregistrer le membre
                    </button>
                </div>

            </form>

        </div>
    </div>



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
                                        <h1 class="mb-0 d-inline-block fs-6 lh-1">{{ __("Historique d'adhésions des
                                            membres") }}</h1>
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
            <div class="">

                <div class="table-wrapper">

                    <div class="card has-actions has-filter">
                        <div class="card-header">
                            <div class="w-100 justify-content-between d-flex flex-wrap align-items-center gap-1">

                                <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-1">
                                    <button class="btn btn-show-table-options" type="button">Rechercher</button>

                                    <div class="table-search-input">
                                        <label>
                                            <input type="search" wire:model.live="search" class="form-control input-sm"
                                                placeholder="Rechercher..." style="min-width: 120px">
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-1">
                                    @can('isAdmin')
                                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                        + {{ __('Nouvel utilisateur') }}
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-table">
                            <div class="table-responsive table-has-actions table-has-filter">
                                <table
                                    class="table card-table table-vcenter table-striped table-hover dataTable no-footer dtr-inline collapsed">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Postnom</th>
                                            <th>Email</th>
                                            <th>Rôle</th>
                                            <th>Date création</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($users as $user)
                                        <tr class="odd">
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->postnom }}</td>
                                            <td>{{ $user->email }}</td>

                                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                @if ($user->status)
                                                <span class="badge bg-label-success me-1">Actif</span>
                                                @else
                                                <span class="badge bg-label-secondary me-1">Inactif</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <div class="alert alert-danger" role="alert">
                                                    Aucune information disponible pour le moment
                                                </div>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div
                            class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    <label>
                                        <select wire:model.lazy="perPage" class="form-select form-select-sm">
                                            <option value="10">10</option>
                                            <option value="30">30</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="999999">Tous</option>
                                        </select>
                                    </label>
                                </div>
                                <div class="text-muted">
                                    Affichage de {{ $users->firstItem() }} à {{ $users->lastItem() }} sur
                                    <span class="badge bg-secondary">{{ $users->total() }}</span> enregistrements
                                </div>
                            </div>

                            <div class="d-flex justify-content-center">
                                {{ $users->links() }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
