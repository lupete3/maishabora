<!-- resources/views/livewire/register-member.blade.php -->
<div class="mt-4">

    @include('livewire.user.add-member')

    <!-- resources/views/livewire/view-registered-members.blade.php -->
    <div class="table-wrapper">
        <div class="card has-actions has-filter">

            <div class="card-header border-bottom">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">
                        <div class="input-group input-group-merge w-100 w-md-auto" style="min-width: 250px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                placeholder="Rechercher un utilisateur...">
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2 ms-auto ms-md-0">
                        <select wire:model.live.debounce.300ms="perPage" class="form-select form-select-sm ">
                            <option value="10">10</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="999999">Tous</option>
                        </select>

                        @can('ajouter-utilisateur')
                            <button class="btn btn-primary btn-sm" wire:click='openModal'>
                                <i class="bx bx-plus me-1"></i> Ajouter
                            </button>
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
                                <th>Code</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôles</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($members as $member)
                                <tr>
                                    <td>{{ $member->code }}</td>
                                    <td>{{ $member->name.' '.$member->postnom.' '.$member->prenom }}</td>
                                    <td>{{ $member->email }}</td>
                                    <td>{{ $member->telephone ?? '-' }}</td>
                                    <td>
                                        @foreach ($member->getRoleNames() as $role)
                                            <span class="badge bg-secondary">{{ $role}} </span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if ($member->status)
                                            <span class="badge bg-success">Actif</span>
                                        @else
                                            <span class="badge bg-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            @can ('modifier-utilisateur')
                                                <button wire:click='edit({{ $member->id }})'
                                                    class="btn btn-sm btn-info">{{ __('Modifier') }}</button>
                                            @endif
                                            @can ('supprimer-utilisateur')
                                                <button wire:click='edit({{ $member->id }})'
                                                    class="btn btn-sm btn-danger">{{ __('Supprimer') }}</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-danger" role="alert">
                                            Rechercher un client dans le système.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
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
                    @if ($members)
                        <div class="text-muted">
                            Affichage de {{ $members->firstItem() }} à {{ $members->lastItem() }} sur
                            <span class="badge bg-primary">{{ $members->total() }}</span> membres
                        </div>
                    @endif

                </div>
                @if ($members)
                <div class="d-flex justify-content-center">
                    {{ $members->links() }}
                </div>
                @endif
            </div>

        </div>
    </div>
</div>
