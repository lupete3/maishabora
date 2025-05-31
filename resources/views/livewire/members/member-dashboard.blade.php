<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class=" overflow-hidden shadow-xl sm:rounded-lg p-2">

            <h2 class="text-2xl font-bold mb-4">Tableau de bord - {{ $user->name }} {{ $user->postnom }}</h2>

            <div class="row g-4">
                <!-- Informations personnelles -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">Informations personnelles</div>
                        <div class="card-body pt-4">
                            <h6><strong>Nom :</strong> {{ $user->name }} {{ $user->postnom }}</h6>
                            <h6><strong>Email :</strong> {{ $user->email }}</h6>
                            <h6><strong>Téléphone :</strong> {{ $user->telephone }}</h6>
                            <h6><strong>Date de naissance :</strong> {{ $user->date_naissance }}</h6>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">Statistiques</div>
                        <div class="card-body pt-4">
                            <h6><strong>Total déposé :</strong> {{ number_format($totalDeposited, 0, ',', '.') }} FC</h6>
                            <h6><strong>Total récupéré :</strong> {{ number_format($totalWithdrawn, 0, ',', '.') }} FC</h6>
                            @if ($totalWithdrawn > 0)
                                <h6><strong>Montant soutiré :</strong> {{ number_format($totalDeposited - $totalWithdrawn, 0, ',', '.') }} FC</h6>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Carnets d'adhésion -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">Carnets d'adhésion</div>
                        <div class="card-body pt-4">
                            @if ($membershipCards->isNotEmpty())
                                <ul class="list-group">
                                    @foreach ($membershipCards as $card)
                                        <li class="list-group-item">
                                            Code : {{ $card->code }} | Prix : {{ number_format($card->prix, 0, ',', '.') }} FC |
                                            Date : {{ $card->vendu_a }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <h6>Aucun carnet d'adhésion trouvé.</h6>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Souscriptions -->
                <div class="col-md-12 mt-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">Souscriptions</div>
                        <div class="card-body pt-4">
                            @if ($subscriptions->isNotEmpty())
                                <div class="row">
                                    @foreach ($subscriptions as $subscription)
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <div class="card-body pt-4">
                                                    <h5 class="card-title">{{ __('Montant souscrit') }} : {{ number_format($subscription->montant_souscrit, 0, ',', '.') }} FC</h5>
                                                    <h6><strong>{{ __('Statut') }} :</strong> <span class="badge bg-{{ $subscription->statut == 'retire' ? 'success' : 'info' }}">{{ ucfirst($subscription->statut) }}</span></h6>
                                                    <h6><strong>{{ __('Carnet associé') }} :</strong> {{ optional($subscription->contributionBooks)->code ?? 'Aucun' }}</h6>
                                                    <h6><strong>{{ __('Dépôt total') }} :</strong> {{ number_format(optional($subscription->contributionBooks)->lines->sum('montant'), 0, ',', '.') }} FC</h6>
                                                </div>
                                                @if($subscription->statut == 'retire')

                                                        <a href="{{ route('member.book.pdf', ['book' => $subscription->contributionBooks->id]) }}" class="btn btn-sm btn-primary m-2">
                                                            <i class="menu-icon tf-icons bx bx-download"></i>
                                                            {{ __('Télécharger PDF') }}
                                                        </a>

                                                    @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <h6>Aucune souscription trouvée.</h6>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">Suivi des dépôts</div>
        <div class="card-body">
            @if (!empty($contributionLabels))
                <canvas id="contributionsChart" width="800" height="300"></canvas>

                <script>
                    window.contributionLabels = @json($contributionLabels);
                    window.contributionData = @json($contributionData);

                    // Si le canvas est chargé après, on peut relancer le graphique
                    document.addEventListener('livewire:navigated', () => {
                        const ctx = document.getElementById('contributionsChart');
                        if (ctx && typeof Chart !== 'undefined') {
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: window.contributionLabels,
                                    datasets: [{
                                        label: 'Montant déposé (FC)',
                                        data: window.contributionData,
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                        fill: true,
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: true },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return context.parsed.y + ' FC';
                                                }
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                </script>
            @else
                <p>Aucune donnée disponible pour le graphique.</p>
            @endif
        </div>
    </div>
</div>
            </div>
        </div>
    </div>
</div>
