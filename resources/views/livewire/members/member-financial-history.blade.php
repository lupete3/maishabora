<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-2">

            <h2 class="text-2xl font-bold mb-4">Historique financier</h2>
            <a href="{{ route('member.history.excel') }}" class="btn btn-success mb-3">
                <i class="bi bi-file-earmark-excel"></i> Exporter en Excel
            </a>

            @if ($operations)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Entrée (FC)</th>
                            <th>Sortie (FC)</th>
                            <th>Référence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $solde = 0;
                        @endphp
                        @forelse ($operations as $operation)
                            @php
                                if ($operation->type_operation === 'Adhésion' || $operation->type_operation === 'Contribution')
                                {
                                    $entree = $operation->montant;
                                    $sortie = 0;
                                    $solde += $entree;
                                } else {
                                    $entree = 0;
                                    $sortie = $operation->montant;
                                    $solde -= $sortie;
                                }
                            @endphp
                            <tr>
                                <td>{{ $operation->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $operation->type_operation }}</td>
                                <td>
                                    @if ($operation->reference_type === 'App\Models\MembershipCard')
                                    Achat du carnet {{ optional($operation->reference)->code ?? '-' }}
                                    @elseif ($operation->reference_type === 'App\Models\ContributionLine')
                                    Dépôt quotidien - Carnet {{
                                    optional(optional($operation->reference)->contributionBook)->code ?? '-' }}
                                    @elseif ($operation->reference_type === 'App\Models\ContributionBook')
                                    Retrait du carnet {{ optional($operation->reference)->code ?? '-' }}
                                    @else
                                    Opération inconnue
                                    @endif
                                </td>
                                <td class="text-success">{{ number_format($entree, 0, ',', '.') }}</td>
                                <td class="text-danger">{{ number_format($sortie, 0, ',', '.') }}</td>
                                <td>
                                    @if ($operation->reference_type === 'App\Models\MembershipCard')
                                    {{ optional($operation->reference)->code ?? '-' }}
                                    @elseif ($operation->reference_type === 'App\Models\ContributionLine')
                                    {{ optional(optional($operation->reference)->book)->code ?? '-' }} - Ligne {{
                                    optional($operation->reference)->numero_ligne ?? '-' }}
                                    @elseif ($operation->reference_type === 'App\Models\ContributionBook')
                                    {{ optional($operation->reference)->code ?? '-' }}
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center"><div class="alert alert-danger" role="alert">{{ __('Aucune information disponible pour le moment') }}</div></td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
            @else
            <div class="alert alert-info">Aucune opération trouvée.</div>
            @endif

        </div>
        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">

            <div class="d-flex justify-content-between align-items-center gap-3">

                <!-- Informations sur la pagination -->
                <div class="text-muted">
                    <svg class="icon svg-icon-ti-ti-world" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                        <path d="M3.6 9h16.8"></path>
                        <path d="M3.6 15h16.8"></path>
                        <path d="M11.5 3a17 17 0 0 0 0 18"></path>
                        <path d="M12.5 3a17 17 0 0 1 0 18"></path>
                    </svg>
                    <span class="d-none d-sm-inline">Affichage de</span>
                    {{ $operations->firstItem() }} à {{ $operations->lastItem() }} sur
                    <span class="badge bg-secondary text-secondary-fg">
                        {{ $operations->total() }}
                    </span>
                    <span class="hidden-xs">enregistrements</span>
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $operations->links() }}
            </div>
        </div>
    </div>
</div>
