<!-- resources/views/livewire/member-details.blade.php -->
<div class="mt-4">

    @include('livewire.admin.add-deposit-for-member')
    @include('livewire.admin.add-retrait-for-member')
    @include('livewire.admin.show-card-details')

    <main class="flex-grow mx-auto  py-0">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-lg border bg-card text-card-foreground shadow-lg">
                    <div class="">
                        <div class="card-header border-bottom">
                            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" id="photo-tab" data-bs-toggle="tab" data-bs-target="#photo" type="button" role="tab">
                                        📸 Photo de profil
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" id="signature-tab" data-bs-toggle="tab" data-bs-target="#signature" type="button" role="tab">
                                        ✍️ Signature
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content p-4" id="profileTabsContent">
                            <!-- Onglet Photo -->
                            <div class="tab-pane fade show active text-center" id="photo" role="tabpanel" aria-labelledby="photo-tab">
                                @if ($member->photo_profil)
                                    <img src="{{ asset('storage/' . $member->photo_profil) }}" alt="Photo de profil" alt="Photo de profil" class="shadow" width="90%">
                                @else
                                    <img src="{{ asset('user.png') }}" alt="Photo par défaut" class="rounded-circle shadow" width="120">
                                    <p class="text-muted mt-2">Aucune photo de profil disponible</p>
                                @endif
                            </div>

                            <!-- Onglet Signature -->
                            <div class="tab-pane fade text-center" id="signature" role="tabpanel" aria-labelledby="signature-tab">
                                @if ($member->scan_piece)
                                    <img src="{{ asset('storage/' . $member->scan_piece) }}" alt="Signature" class="img-fluid rounded border shadow-sm" style="max-width: 90%;">
                                @else
                                    <span class="text-sm text-primary card p-2">Aucune signature enregistrée pour ce client</span>
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
                            <p><strong class="font-medium">Noms:</strong> {{ $member->name.'
                                '.$member->postnom.'
                                '.$member->prenom }}</p>
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
                        </div>
                    </div>
                </div>
                <div class="rounded-lg border bg-card text-card-foreground shadow-lg">
                    <div class="flex flex-col space-y-1.5 p-6">
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
                        $balance = number_format($member->accounts->firstWhere('currency', $curr)?->balance ?? 0, 2);
                        $color = $curr === 'USD' ? 'green' : 'blue';
                        @endphp
                        @php
                        $balance = (float) ($member->accounts->firstWhere('currency', $curr)?->balance ?? 0);
                        @endphp
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 bg-secondary/30 rounded-lg shadow">
                            <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                <span class="font-bold text-xl text-{{ $color }}-600">{{ $curr }}</span>
                                <span class="font-medium text-lg"> </span>
                            </div>
                            <span class="text-2xl font-semibold text-foreground">
                                {{ number_format($balance, 2, '.', ' ')
                                }}
                            </span>
                        </div>
                        @endforeach

                    </div>

                    <div class="items-center p-6 pt-0 flex justify-between gap-2">
                        @can('depot-compte-membre')
                        <button wire:click='openDepositModal'
                            class="btn-outline-success inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-arrow-down-to-line mr-2 h-4 w-4">
                                <path d="M12 17V3"></path>
                                <path d="m6 11 6 6 6-6"></path>
                                <path d="M19 21H5"></path>
                            </svg> Dépôt</button>
                            @endcan
                            @can('retrait-compte-membre')
                            <button wire:click='openRetraitModal'
                                class=" inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&amp;_svg]:pointer-events-none [&amp;_svg]:size-4 [&amp;_svg]:shrink-0 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"><svg
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-arrow-up-from-line mr-2 h-4 w-4">
                                <path d="m18 9-6-6-6 6"></path>
                                <path d="M12 3v14"></path>
                                <path d="M5 21h14"></path>
                            </svg> Retrait</button>
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
                                <input type="search" wire:model.live.debounce.300ms="search"  placeholder="Rechercher transactions..." class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background
                          file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground
                          placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2
                          focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed
                          disabled:opacity-50 md:text-sm pl-8 w-full">
                            </div>

                            <a href="{{ route('member.transactions.export', ['id' => $member->id]) }}" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium
                                ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2
                                focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none
                                disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground
                                h-9 rounded-md px-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-download mr-2 h-4 w-4">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" x2="12" y1="15" y2="3"></line>
                                </svg>
                                Télécharger PDF
                            </a>
                        </div>
                    </div>
                    <div dir="ltr" class="relative overflow-hidden h-[400px] w-full rounded-md border"
                        style="position: relative; --radix-scroll-area-corner-width: 0px; --radix-scroll-area-corner-height: 0px;">
                        <style>
                            [data-radix-scroll-area-viewport] {
                                scrollbar-width: none;
                                -ms-overflow-style: none;
                                -webkit-overflow-scrolling: touch;
                            }

                            [data-radix-scroll-area-viewport]::-webkit-scrollbar {
                                display: none
                            }
                        </style>

                        <div data-radix-scroll-area-viewport="" class="h-full w-full rounded-[inherit]"
                            style="overflow: scroll;">
                            <div style="min-width: 100%; display: table;">
                                <div class="relative w-full overflow-auto">
                                    <table class="w-full caption-bottom text-sm">
                                        <thead class="[&amp;_tr]:border-b sticky top-0 bg-card z-10">
                                            <tr
                                                class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                <th
                                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0 w-[150px]">
                                                    Date</th>
                                                <th
                                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0">
                                                    Description</th>
                                                <th
                                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0 w-[150px]">
                                                    Type</th>
                                                <th
                                                    class="h-12 px-4 align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0 text-right w-[150px]">
                                                    Montant</th>
                                                <th
                                                    class="h-12 px-4 align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0 text-right w-[150px]">
                                                    Solde après</th>
                                                <th
                                                    class="h-12 px-4 align-middle font-medium text-muted-foreground [&amp;:has([role=checkbox])]:pr-0 w-[100px]">
                                                    Action</th>
                                            </tr>
                                        </thead>

                                        <tbody class="[&amp;_tr:last-child]:border-0">
                                            @forelse ($transactions as $transaction)
                                            <tr
                                                class="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                <td
                                                    class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0 font-medium">
                                                    <div class="flex items-center gap-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="lucide lucide-calendar-days h-4 w-4 text-muted-foreground">
                                                            <path d="M8 2v4"></path>
                                                            <path d="M16 2v4"></path>
                                                            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
                                                            <path d="M3 10h18"></path>
                                                            <path d="M8 14h.01"></path>
                                                            <path d="M12 14h.01"></path>
                                                            <path d="M16 14h.01"></path>
                                                            <path d="M8 18h.01"></path>
                                                            <path d="M12 18h.01"></path>
                                                            <path d="M16 18h.01"></path>
                                                        </svg>
                                                        {{ $transaction->created_at->format('d/m/Y H:i') }}
                                                    </div>
                                                </td>

                                                <td class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0">
                                                    {{ $transaction->description }}
                                                </td>

                                                <td class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0">
                                                    <div class="rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors
                                                        focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2
                                                        text-foreground flex items-center gap-2 capitalize">
                                                        @if ($transaction->type === 'dépôt')
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="lucide lucide-arrow-down-to-line h-5 w-5 text-green-500">
                                                            <path d="M12 17V3"></path>
                                                            <path d="m6 11 6 6 6-6"></path>
                                                            <path d="M19 21H5"></path>
                                                        </svg>
                                                        <span>{{ ucfirst($transaction->type) }}</span>
                                                        @elseif ($transaction->type === 'retrait')
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="lucide lucide-arrow-up-from-line h-5 w-5 text-red-500">
                                                            <path d="m18 9-6-6-6 6"></path>
                                                            <path d="M12 3v14"></path>
                                                            <path d="M5 21h14"></path>
                                                        </svg>
                                                        <span>{{ ucfirst($transaction->type) }}</span>
                                                        @else
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            class="lucide lucide-arrow-right-left h-5 w-5 text-blue-500">
                                                            <path d="m16 3 4 4-4 4"></path>
                                                            <path d="M20 7H4"></path>
                                                            <path d="m8 21-4-4 4-4"></path>
                                                            <path d="M4 17h16"></path>
                                                        </svg>
                                                        <span>{{ ucfirst($transaction->type) }}</span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td
                                                    class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0 text-right font-semibold">
                                                    @if($transaction->type === 'retrait') -@endif{{
                                                    number_format($transaction->amount, 2) }} {{ $transaction->currency
                                                    }}
                                                </td>

                                                <td
                                                    class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0 text-right font-semibold">
                                                    {{ number_format($transaction->balance_after, 2) }} {{
                                                    $transaction->currency }}
                                                </td>

                                                <td class="p-4 align-middle [&amp;:has([role=checkbox])]:pr-0">
                                                    <button type="button"
                                                        wire:click="$dispatch('facture-validee', { url: '{{ route('receipt.generate_pos', ['id' => $transaction->id]) }}' })"
                                                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium
                                                            ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2
                                                            focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none
                                                            disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground
                                                            h-9 rounded-md px-3">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-printer mr-2 h-4 w-4">
                                                            <path d="M6 9V2h12v7"></path>
                                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                            <path d="M6 14h12v4H6v-4z"></path>
                                                        </svg> POS
                                                    </button>
                                                    <button type="button"
                                                        wire:click="$dispatch('facture-validee', { url: '{{ route('receipt.generate', ['id' => $transaction->id]) }}' })"
                                                        class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium
                                                            ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2
                                                            focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none
                                                            disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground
                                                            h-9 rounded-md px-3 mt-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                            viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-printer mr-2 h-4 w-4 ">
                                                            <path d="M6 9V2h12v7"></path>
                                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                                            <path d="M6 14h12v4H6v-4z"></path>
                                                        </svg>PC
                                                    </button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="6" class="p-4 align-middle text-center">
                                                    <div class="alert alert-danger" role="alert">
                                                        Aucune transaction trouvée.
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <div class="text-muted">
                                Affichage de {{ $transactions->firstItem() }} à {{ $transactions->lastItem() }} sur
                                <span class="badge bg-primary">{{ $transactions->total() }}</span> transactions
                            </div>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title m-0 me-2">Cartes de membre associées</h5>
                    </div>
                    <div class="card-body"></div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Mise</th>
                                        <th>Total Déposé</th>
                                        <th>Statut</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($allCards as $card)
                                        <tr>
                                            <td>{{ $card->code }}</td>
                                            <td>{{ $card->subscription_amount }} {{ $card->currency }}</td>
                                            <td>{{ $card->contributions->where('is_paid', '=', 1)->sum('amount') }} {{ $card->currency }}</td>
                                            <td>
                                                @if($card->is_active)
                                                <span class="badge bg-success">Active</span>
                                                @else
                                                <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button wire:click="openCardViewModal({{ $card->id }})" class="btn btn-info btn-sm">
                                                    Voir Détails
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center">Aucune carte de membre trouvée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <div class="col-md-12 mt-4">
                <livewire:credit.client-credit-situation :user-id="$member->id" />
            </div>
        </div>

    </main>

</div>
