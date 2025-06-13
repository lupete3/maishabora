<!-- resources/views/livewire/register-member.blade.php -->
<div class="mt-4">

    @include('livewire.members.add-member')

    <!-- resources/views/livewire/view-registered-members.blade.php -->
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

                    <div class="d-flex align-items-center gap-2">
                        <select wire:model.live="perPage" class="form-select form-select-sm">
                            <option value="10">10</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="999999">Tous</option>
                        </select>
                        <button class="btn btn-sm action-item btn-primary" wire:click='openModal'>
                            {{ __("Ajouter") }}
                        </button>
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
                                <th>Solde USD</th>
                                <th>Solde CDF</th>
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
                                        {{ number_format($member->accounts->firstWhere('currency', 'USD')?->balance ?? 0, 2) }}
                                    </td>
                                    <td>
                                        {{ number_format($member->accounts->firstWhere('currency', 'CDF')?->balance ?? 0, 2) }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ route('member.details', $member->id) }}" wire:navigate class="btn btn-sm btn-primary">Compte Client</a>
                                            <button wire:click='edit({{ $member->id }})'
                                                class="btn btn-sm btn-info">{{ __('Modifier') }}</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-danger" role="alert">
                                            Aucun membre trouvé.
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
                    <div class="text-muted">
                        Affichage de {{ $members->firstItem() }} à {{ $members->lastItem() }} sur
                        <span class="badge bg-primary">{{ $members->total() }}</span> membres
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $members->links() }}
                </div>
            </div>

        </div>
    </div>
</div>
