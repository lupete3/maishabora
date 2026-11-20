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

    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-danger mb-3">
                <i class="bx bx-lock-alt me-1"></i>
                <strong>Fichier confidentiel :</strong> il contient toutes les données des membres et les mots de passe chiffrés.
                Conservez-le sur un support sûr (clé USB chiffrée, Drive protégé). Ne l'envoyez jamais par WhatsApp ou e-mail.
            </div>

            <ul class="small text-muted mb-3">
                <li>Votre <strong>mot de passe vous sera redemandé</strong> avant le téléchargement.</li>
                <li>La sauvegarde est une photo de la base <strong>au moment du clic</strong> ; les opérations en cours ne sont pas bloquées.</li>
                <li>Le fichier n'est pas conservé sur le serveur. Chaque export est enregistré dans le journal ci-dessous.</li>
                <li>Une sauvegarde au maximum toutes les 10 minutes, pour ménager le serveur.</li>
            </ul>

            @if ($bloque)
                <button class="btn btn-secondary" disabled>
                    <i class="bx bx-time me-1"></i> Prochaine sauvegarde possible dans {{ (int) ceil($attente / 60) }} minute(s)
                </button>
            @else
                <a href="{{ route('sauvegarde.download') }}" class="btn btn-primary"
                    onclick="this.classList.add('disabled'); this.innerHTML='<span class=\'spinner-border spinner-border-sm me-1\'></span> Préparation de la sauvegarde…';">
                    <i class="bx bx-download me-1"></i> Télécharger la sauvegarde (.sql.gz)
                </a>
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
