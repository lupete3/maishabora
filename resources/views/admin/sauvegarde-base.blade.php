@extends('layouts.backend')

@section('title', 'Sauvegarde de la base')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="mb-1"><i class="bx bx-data me-1"></i> Sauvegarde de la base de données</h4>
    <p class="text-muted">
        Télécharge une copie complète de la base (toutes les opérations, clients, comptes, crédits…)
        au format <strong>.sql.gz</strong>, réimportable dans MySQL.
    </p>

    @if (session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Sauvegardes automatiques (cron-job.org → /cron/sauvegarde/<jeton>) --}}
    <div class="card mb-4 {{ $alerte ? 'border border-danger' : '' }}">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold"><i class="bx bx-time-five me-1"></i> Sauvegardes automatiques (sur le serveur)</span>
            <form method="POST" action="{{ route('sauvegarde.creer') }}"
                onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'spinner-border spinner-border-sm me-1\'></span> Sauvegarde en cours…';">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary" @disabled($creationBloquee)>
                    <i class="bx bx-save me-1"></i> Créer une sauvegarde sur le serveur maintenant
                </button>
            </form>
        </div>
        <div class="card-body">
            @if (! $cronActif)
                <div class="alert alert-warning mb-3">
                    L'adresse cron de sauvegarde n'est pas activée : ajoutez <code>CRON_SAUVEGARDE_TOKEN</code> dans le fichier
                    <code>.env</code> du serveur, puis programmez son appel sur cron-job.org (voir la documentation).
                </div>
            @endif

            @if ($alerte)
                <div class="alert alert-danger mb-3">
                    <i class="bx bx-error me-1"></i>
                    @if ($ageDerniere === null)
                        Aucune sauvegarde automatique n'existe encore sur le serveur.
                    @else
                        La dernière sauvegarde automatique date de {{ number_format($ageDerniere, 0) }} heures :
                        l'appel cron-job.org ne fonctionne peut-être plus.
                    @endif
                </div>
            @else
                <p class="text-success mb-3">
                    <i class="bx bx-check-circle me-1"></i>
                    Dernière sauvegarde il y a {{ $ageDerniere < 1 ? 'moins d\'une heure' : number_format($ageDerniere, 0) . ' h' }}.
                </p>
            @endif

            <p class="small text-muted mb-2">Une sauvegarde par jour au maximum, conservée {{ $retention }} jours (les plus anciennes sont supprimées automatiquement).</p>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fichier</th>
                            <th>Date</th>
                            <th class="text-end">Taille</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($automatiques as $fichier)
                            <tr>
                                <td class="small">{{ $fichier['nom'] }}</td>
                                <td class="text-nowrap">{{ $fichier['date']->format('d/m/Y H:i') }}</td>
                                <td class="text-end text-nowrap">{{ number_format($fichier['taille'] / 1024, 0, ',', ' ') }} Ko</td>
                                <td class="text-end">
                                    <a href="{{ route('sauvegarde.fichier', $fichier['nom']) }}" class="btn btn-sm btn-outline-secondary" download>
                                        <i class="bx bx-download"></i> Télécharger
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Aucune sauvegarde automatique pour l'instant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h5 class="mt-2">Télécharger une sauvegarde instantanée</h5>

    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-danger mb-3">
                <i class="bx bx-lock-alt me-1"></i>
                <strong>Fichier confidentiel :</strong> il contient toutes les données des membres et les mots de passe chiffrés.
                Conservez-le sur un support sûr (clé USB chiffrée, Drive protégé). Ne l'envoyez jamais par WhatsApp ou e-mail.
            </div>

            <ul class="small text-muted mb-3">
                <li>Votre <strong>mot de passe a été redemandé</strong> à l'ouverture de cette page ; le téléchargement reste possible pendant 15 minutes, ensuite il sera redemandé.</li>
                <li>La sauvegarde est une photo de la base <strong>au moment du clic</strong> ; les opérations en cours ne sont pas bloquées.</li>
                <li>Le fichier n'est pas conservé sur le serveur. Chaque export est enregistré dans le journal ci-dessous.</li>
                <li>Une sauvegarde au maximum toutes les 10 minutes, pour ménager le serveur.</li>
            </ul>

            @if ($bloque)
                <button class="btn btn-secondary" disabled>
                    <i class="bx bx-time me-1"></i> Prochaine sauvegarde possible dans {{ (int) ceil($attente / 60) }} minute(s)
                </button>
            @else
                {{-- Lien classique (pas de navigation Livewire) : le navigateur télécharge le fichier --}}
                <a href="{{ route('sauvegarde.download') }}" class="btn btn-primary" id="btn-sauvegarde" download>
                    <i class="bx bx-download me-1"></i> Télécharger la sauvegarde (.sql.gz)
                </a>
                <div id="info-sauvegarde" class="small text-muted mt-2 d-none">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Le téléchargement démarre dans quelques secondes (préparation du fichier)… Ne fermez pas la page.
                </div>
                <script>
                    document.getElementById('btn-sauvegarde').addEventListener('click', function () {
                        const bouton = this, info = document.getElementById('info-sauvegarde');
                        bouton.classList.add('disabled');
                        info.classList.remove('d-none');
                        // Le navigateur reste sur la page pendant un téléchargement : on rétablit l'affichage
                        setTimeout(function () {
                            info.innerHTML = 'Téléchargement lancé. Vérifiez le dossier « Téléchargements » de votre navigateur.';
                        }, 15000);
                    });
                </script>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header fw-bold">Dernières sauvegardes</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Par</th>
                        <th>Détail</th>
                        <th>Adresse IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($derniers as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ trim(($log->user->name ?? '—') . ' ' . ($log->user->postnom ?? '')) }}</td>
                            <td class="small {{ str_contains($log->description, 'ÉCHEC') ? 'text-danger' : '' }}">{{ $log->description }}</td>
                            <td class="small text-muted">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Aucune sauvegarde pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
