<div>
    <!-- Modal Sell Membership Card-->
    @include('livewire.members.add-sell-members')

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

                                    <button class="btn   btn-show-table-options" type="button"> {{ __('Rechercher') }} </button>

                                    <div class="table-search-input">
                                        <label>
                                            <input type="search" wire:model.live='search' class="form-control input-sm"
                                                placeholder="{{ __('Rechercher...') }}" style="min-width: 120px">
                                        </label>
                                    </div>

                                </div>
                                <div class="d-flex align-items-center gap-1">

                                    <button class="btn action-item btn-primary" wire:click='openModal'>
                                        + {{ __("Vendre une carte") }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card-table">
                            <div class="table-responsive table-has-actions table-has-filter">
                                <div id="botble-page-tables-page-table_wrapper"
                                    class="dataTables_wrapper dt-bootstrap5 no-footer">

                                    <div id="botble-page-tables-page-table_processing"
                                        class="dataTables_processing card" role="status" style="display: none;">
                                        <div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                            <div></div>
                                        </div>
                                    </div>
                                    <table
                                        class="table card-table table-vcenter table-striped table-hover dataTable no-footer dtr-inline collapsed"
                                        id="botble-page-tables-page-table"
                                        aria-describedby="botble-page-tables-page-table_info">
                                        <thead>
                                            <tr>
                                                <th>{{ __("Code Carte") }}</th>
                                                <th>{{ __("Montant") }} (Fc)</th>
                                                <th>{{ __("Nom Membre") }}</th>
                                                <th>{{ __("Postnom Membre") }}</th>
                                                <th>{{ __("Date Souscription") }}</th>
                                                <th>{{ __("Statut") }}</th>
                                                <th>{{ __("Action") }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($shipCards as $item)

                                                <tr class="odd">
                                                    <td><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong> {{
                                                            $item->code }}
                                                        </strong></td>
                                                    <td>{{ $item->prix }}</td>
                                                    <td>{{ $item->user->name }}</td>
                                                    <td>{{ $item->user->postnom }}</td>
                                                    <td>{{ $item->created_at }}</td>
                                                    <td><span class="badge bg-label-primary me-1">Active</span></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                                data-bs-toggle="dropdown">
                                                                <i class="bx bx-dots-vertical-rounded"></i>
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <a class="dropdown-item" wire:click='openEditModal({{ $item->id }})' href="javascript:void(0);"><i
                                                                        class="bx bx-edit-alt me-1"></i> Edit</a>
                                                                <a class="dropdown-item" wire:click='sendConfirm({{ $item->id }}, "warning", "Voulez-vous supprimer cette opération ?", "Supprimer")' href="javascript:void(0);"><i
                                                                        class="bx bx-trash me-1"></i> Delete</a>

                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>

                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center"><div class="alert alert-danger" role="alert">{{ __('Aucune information disponible pour le moment') }}</div></td>
                                                </tr>

                                            @endforelse
                                        </tbody>
                                    </table>
                                    <div
                                        class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">

                                        <div class="d-flex justify-content-between align-items-center gap-3">

                                            <!-- Filtre nombre d’éléments par page -->
                                            <div>
                                                <label>
                                                    <select wire:model.lazy="perPage" class="form-select form-select-sm">
                                                        <option value="10">10</option>
                                                        <option value="30">30</option>
                                                        <option value="50">50</option>
                                                        <option value="100">100</option>
                                                        <option value="500">500</option>
                                                        <option value="999999">Tous</option>
                                                    </select>
                                                </label>
                                            </div>

                                            <!-- Informations sur la pagination -->
                                            <div class="text-muted">
                                                <svg class="icon svg-icon-ti-ti-world"
                                                    xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                                    <path d="M3.6 9h16.8"></path>
                                                    <path d="M3.6 15h16.8"></path>
                                                    <path d="M11.5 3a17 17 0 0 0 0 18"></path>
                                                    <path d="M12.5 3a17 17 0 0 1 0 18"></path>
                                                </svg>
                                                <span class="d-none d-sm-inline">{{ __('Affichage de') }}</span>
                                                {{ $shipCards->firstItem() }} {{ __('à') }} {{ $shipCards->lastItem() }} {{ __('sur') }}
                                                <span class="badge bg-secondary text-secondary-fg">
                                                    {{ $shipCards->total() }}
                                                </span>
                                                <span class="hidden-xs">{{ __('enregistrements') }}</span>
                                            </div>
                                        </div>

                                        <!-- Pagination -->
                                        <div class="d-flex justify-content-center">
                                            {{ $shipCards->links() }}
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

