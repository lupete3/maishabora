<!-- resources/views/livewire/members/member-details.blade.php -->
<div class="mt-4">

    @include('livewire.admin.add-deposit-for-member')
    @include('livewire.admin.add-retrait-for-member')
    @include('livewire.admin.show-card-details')
    @include('livewire.members.partials.modals-management')

    <main class="flex-grow mx-auto  py-0">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-lg border bg-card text-card-foreground shadow-lg">
                    <div class="">
                        <div class="card-header border-bottom">
                            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="photo-tab" data-bs-toggle="tab"
                                        data-bs-target="#photo" type="button" role="tab">
                                        📸 Photo de profil
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="signature-tab" data-bs-toggle="tab"
                                        data-bs-target="#signature" type="button" role="tab">
                                        ✍️ Signature
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-4" id="profileTabsContent">
                            <!-- Onglet Photo -->
                            <div class="tab-pane fade show active text-center" id="photo" role="tabpanel"
                                aria-labelledby="photo-tab">
                                @if ($member->photo_profil && file_exists(public_path('storage/' . $member->photo_profil)))
                                    <img src="{{ asset('storage/' . $member->photo_profil) }}" alt="Photo de profil"
                                        class="shadow" width="90%">
                                @else
                                    <div class="mb-3">
                                        <div class="avatar avatar-xl rounded-circle bg-label-primary fs-2 mx-auto d-flex align-items-center justify-content-center shadow-sm"
                                            style="width: 80px; height: 80px;">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}{{ strtoupper(substr($member->postnom, 0, 1)) }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Onglet Signature -->
                            <div class="tab-pane fade text-center" id="signature" role="tabpanel"
                                aria-labelledby="signature-tab">
                                @if ($member->scan_piece)
                                    <img src="{{ asset('storage/' . $member->scan_piece) }}" alt="Signature"
                                        class="img-fluid rounded border shadow-sm" style="max-width: 90%;">
                                @else
                                    <span class="text-sm text-primary card p-2">Aucune signature enregistrée pour ce
                                        client</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="p-6 pt-0 space-y-3 text-sm">
                        <div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-user h-4 w-4 text-muted-foreground">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <p><strong class="font-medium">Noms:</strong>
                                {{ $member->name . ' ' . $member->postnom . ' ' . $member->prenom }}</p>
                        </div>
                        <div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-map-pin h-4 w-4 text-muted-foreground">
                                <path
                                    d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0">
                                </path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <p><strong class="font-medium">Addresse:</strong> {{ $member->adresse_physique }}</p>
                        </div>
                        <div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-phone h-4 w-4 text-muted-foreground">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                                </path>
                            </svg>
                            <p><strong class="font-medium">Téléphone:</strong> {{ $member->telephone }}</p>
                        </div>
                        <div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-mail h-4 w-4 text-muted-foreground">
                                <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                            <p><strong class="font-medium">Email:</strong> {{ $member->email }}</p>
                        </div>
                        <div class="flex items-center gap-3"><svg xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"
                                class="lucide lucide-info h-4 w-4 text-muted-foreground">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M12 16v-4"></path>
                                <path d="M12 8h.01"></path>
                            </svg>
                            <p><strong class="font-medium">ID Client:</strong> {{ $member->code }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('member.print', $member->id) }}" wire:navigate
                                class="btn btn-primary btn-sm"> Imprimer Fiche Client</a>
                            @can('modifier-visible-compte')
                                <button wire:click="toggleVisibleAccount({{ $member->id }})"
                                    class="btn {{ $member->visible_account ? 'btn-danger' : 'btn-primary' }} btn-sm">{{ $member->visible_account ? 'Masquer les soldes' : 'Afficher les soldes' }}</button>
                            @endcan
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card text-card-foreground shadow-lg">
                    <div class="p-6">
                        <div class="font-semibold tracking-tight flex items-center gap-3 text-xl"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-landmark h-6 w-6 text-primary">
                                <line x1="3" x2="21" y1="22" y2="22"></line>
                                <line x1="6" x2="6" y1="18" y2="11"></line>
                                <line x1="10" x2="10" y1="18" y2="11"></line>
                                <line x1="14" x2="14" y1="18" y2="11"></line>
                                <line x1="18" x2="18" y1="18" y2="11"></line>
                                <polygon points="12 2 20 7 4 7"></polygon>
                            </svg>Balances des comptes</div>
                    </div>
                    <div class="p-6 pt-0 space-y-4">
                        @foreach(['USD', 'CDF'] as $curr)
                            @php
                                $balance = (float) ($member->accounts->firstWhere('currency', $curr)?->balance ?? 0);
                                $color = $curr === 'USD' ? 'green' : 'blue';
                            @endphp
                            <div
                                class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 bg-secondary/30 rounded-lg shadow">
                                <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                    <span class="font-bold text-xl text-{{ $color }}-600">{{ $curr }}</span>
                                </div>
                                <span class="text-2xl font-semibold text-foreground flex items-center gap-2">
                                    @if ($member->visible_account)
                                        {{ number_format($balance, 2, '.', ' ') }}
                                        @can('modifier-solde-compte')
                                            <button
                                                wire:click="confirmUpdateBalance({{ $member->accounts->firstWhere('currency', $curr)?->id }})"
                                                class="btn btn-link btn-sm p-0 text-primary" title="Modifier le solde">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round" class="lucide lucide-pencil">
                                                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                                                    <path d="m15 5 4 4" />
                                                </svg>
                                            </button>
                                        @endcan
                                    @else
                                        ****
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="items-center p-6 pt-0 flex justify-between gap-2">
                        @can('depot-compte-membre')
                            <button wire:click='openDepositModal'
                                class="btn-outline-success inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4 py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-arrow-down-to-line mr-2 h-4 w-4">
                                    <path d="M12 17V3"></path>
                                    <path d="m6 11 6 6 6-6"></path>
                                    <path d="M19 21H5"></path>
                                </svg> Dépôt
                            </button>
                        @endcan
                        @can('retrait-compte-membre')
                            <button wire:click='openRetraitModal'
                                class="inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4 py-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-arrow-up-from-line mr-2 h-4 w-4">
                                    <path d="m18 9-6-6-6 6"></path>
                                    <path d="M12 3v14"></path>
                                    <path d="M5 21h14"></path>
                                </svg> Retrait
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-card p-4 sm:p-6 rounded-lg shadow-lg">
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-4 gap-2">
                        <h4 class="text-xl font-semibold flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-list-filter h-6 w-6 text-primary">
                                <path d="M3 6h18"></path>
                                <path d="M7 12h10"></path>
                                <path d="M10 18h4"></path>
                            </svg>
                            Historique des transactions
                        </h4>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="lucide lucide-search absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.3-4.3"></path>
                                </svg>
                                <input type="search" wire:model.live.debounce.300ms="search"
                                    placeholder="Rechercher transactions..."
                                    class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm pl-8 w-full">
                            </div>

                            @php
                                $exportUrl = route('member.transactions.export', ['id' => $member->id]) . '?filter=' . $date_filter;
                                if ($date_filter === 'custom' && $date_from && $date_to) {
                                    $exportUrl .= '&date_from=' . $date_from . '&date_to=' . $date_to;
                                }
                            @endphp
                            <a href="{{ $exportUrl }}"
                                class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium border border-input bg-background hover:bg-accent h-9 rounded-md px-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-download mr-2 h-4 w-4">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" x2="12" y1="15" y2="3"></line>
                                </svg> PDF
                            </a>
                        </div>
                    </div>

                    <!-- Section de filtrage par date -->
                    <div class="mb-4 p-3 bg-secondary/10 rounded-lg border border-secondary/20">
                        <div class="flex flex-wrap gap-2 items-center justify-between">
                            <div class="btn-group" role="group">
                                <button type="button" wire:click="$set('date_filter', '30_days')"
                                    class="btn btn-sm {{ $date_filter === '30_days' ? 'btn-primary' : 'btn-outline-secondary' }}">30
                                    jours</button>
                                <button type="button" wire:click="$set('date_filter', '3_months')"
                                    class="btn btn-sm {{ $date_filter === '3_months' ? 'btn-primary' : 'btn-outline-secondary' }}">3
                                    mois</button>
                                <button type="button" wire:click="$set('date_filter', 'custom')"
                                    class="btn btn-sm {{ $date_filter === 'custom' ? 'btn-primary' : 'btn-outline-secondary' }}">Personnalisé</button>
                            </div>

                            <div class="flex gap-2 items-center">
                                <span class="badge bg-info text-dark">
                                    @if($date_filter === '30_days') 30 derniers jours
                                    @elseif($date_filter === '3_months') 3 derniers mois
                                    @else {{ $date_from ? \Carbon\Carbon::parse($date_from)->format('d/m') : '...' }} -
                                        {{ $date_to ? \Carbon\Carbon::parse($date_to)->format('d/m') : '...' }}
                                    @endif
                                </span>
                                <span class="badge bg-secondary">{{ $transactions->total() }} txn</span>
                            </div>
                        </div>

                        @if($date_filter === 'custom')
                            <div class="flex gap-2 mt-3 items-end">
                                <div class="flex-1">
                                    <label class="text-xs font-medium">Du</label>
                                    <input type="date" wire:model="date_from" class="form-control form-control-sm">
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs font-medium">Au</label>
                                    <input type="date" wire:model="date_to" class="form-control form-control-sm">
                                </div>
                                <button type="button" wire:click="applyCustomFilter"
                                    class="btn btn-primary btn-sm">Ok</button>
                            </div>
                        @endif
                    </div>

                    <div class="relative overflow-hidden w-full rounded-md border">
                        <div class="w-full overflow-auto">
                            <table class="w-full text-sm">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th class="p-3 text-left font-medium w-[120px]">Date</th>
                                        <th class="p-3 text-left font-medium">Description</th>
                                        <th class="p-3 text-left font-medium w-[120px]">Type</th>
                                        <th class="p-3 text-right font-medium w-[120px]">Montant</th>
                                        @if ($member->visible_account)
                                            <th class="p-3 text-right font-medium w-[120px]">Solde</th>
                                        @endif
                                        <th class="p-3 text-center font-medium w-[100px]">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @forelse ($transactions as $transaction)
                                        <tr class="hover:bg-muted/30 transition-colors">
                                            <td class="p-3 text-xs">
                                                {{ $transaction->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="p-3">{{ $transaction->description }}</td>
                                            <td class="p-3 text-xs">
                                                <div class="flex items-center gap-1 capitalize">
                                                    @if ($transaction->type === 'dépôt') <span
                                                        class="text-green-500">⬇️</span>
                                                    @elseif ($transaction->type === 'retrait') <span
                                                        class="text-red-500">⬆️</span>
                                                    @else <span class="text-blue-500">🔄</span> @endif
                                                    {{ $transaction->type }}
                                                </div>
                                            </td>
                                            <td class="p-3 text-right font-semibold">
                                                @if($transaction->type === 'retrait')
                                                -@endif{{ number_format($transaction->amount, 2) }}
                                                {{ $transaction->currency }}
                                            </td>
                                            @if ($member->visible_account)
                                                <td class="p-3 text-right font-medium text-muted-foreground">
                                                    {{ number_format($transaction->balance_after, 2) }}
                                                    {{ $transaction->currency }}
                                                </td>
                                            @endif
                                            <td class="p-3">
                                                <div class="flex flex-col gap-1">
                                                    <div class="flex gap-1">
                                                        <button type="button"
                                                            wire:click="$dispatch('facture-validee', { url: '{{ route('receipt.generate_pos', ['id' => $transaction->id]) }}' })"
                                                            class="flex-1 btn btn-outline-secondary btn-xs py-0"
                                                            title="POS">POS</button>
                                                        <button type="button"
                                                            wire:click="$dispatch('facture-validee', { url: '{{ route('receipt.generate', ['id' => $transaction->id]) }}' })"
                                                            class="flex-1 btn btn-outline-secondary btn-xs py-0"
                                                            title="PC">PC</button>
                                                    </div>
                                                    @can('modifier-transaction-compte')
                                                        <div class="flex gap-1">
                                                            <button type="button"
                                                                wire:click="confirmEditTransaction({{ $transaction->id }})"
                                                                class="flex-1 btn btn-outline-primary btn-xs py-0"
                                                                title="Modifier">✏️</button>
                                                            <button type="button"
                                                                wire:click="confirmDeleteTransaction({{ $transaction->id }})"
                                                                class="flex-1 btn btn-outline-danger btn-xs py-0"
                                                                title="Supprimer">🗑️</button>
                                                        </div>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="p-8 text-center text-muted-foreground italic">
                                                Aucune transaction trouvée pour cette période.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-muted-foreground">
                        <div>
                            {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} sur
                            {{ $transactions->total() }}
                        </div>
                        <div>
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-card shadow-lg overflow-hidden">
                    <div class="p-4 border-b">
                        <h5 class="font-semibold m-0">Cartes de membre associées</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover m-0">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th>Code</th>
                                    <th>Mise</th>
                                    <th>Total Déposé</th>
                                    <th>Statut</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allCards as $card)
                                    <tr>
                                        <td>{{ $card->code }}</td>
                                        <td>{{ number_format($card->subscription_amount, 2) }} {{ $card->currency }}</td>
                                        <td>{{ number_format($card->contributions->where('is_paid', true)->sum('amount'), 2) }}
                                            {{ $card->currency }}</td>
                                        <td>
                                            @if($card->is_active) <span class="badge bg-success">Active</span>
                                            @else <span class="badge bg-secondary">Inactive</span> @endif
                                        </td>
                                        <td class="text-center">
                                            <button wire:click="openCardViewModal({{ $card->id }})"
                                                class="btn btn-info btn-xs">Voir</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Aucune carte trouvée.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <livewire:credit.client-credit-situation :user-id="$member->id" />
        </div>
    </main>
</div>