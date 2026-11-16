<div class="rounded-lg border bg-card text-card-foreground shadow-sm">
    <div class="p-3 border-b flex items-center justify-between gap-2">
        <div class="font-semibold tracking-tight flex items-center gap-2 text-lg">
            <i class="bx bx-mobile-alt text-primary"></i>
            <span>Accès application mobile</span>
        </div>

        {{-- Statut de l'accès --}}
        @if (! $member->mobile_activated_at)
            <span class="badge bg-label-secondary">Non activé</span>
        @elseif ($member->temporaryPasswordExpired())
            <span class="badge bg-label-danger">Code expiré</span>
        @elseif ($member->must_change_password)
            <span class="badge bg-label-warning">En attente</span>
        @else
            <span class="badge bg-label-success">Actif</span>
        @endif
    </div>

    <div class="p-3 space-y-2 text-sm">
        <p class="mb-1"><strong>Identifiant mobile :</strong> <span class="fw-bold">{{ $shortCode }}</span></p>

        @if ($member->mobile_activated_at)
            <p class="mb-1"><strong>Activé le :</strong> {{ $member->mobile_activated_at->format('d/m/Y H:i') }}</p>

            @if ($member->must_change_password && $member->temporary_password_expires_at)
                <p class="mb-1 {{ $member->temporaryPasswordExpired() ? 'text-danger' : 'text-warning' }}">
                    Code provisoire
                    {{ $member->temporaryPasswordExpired() ? 'expiré depuis le' : 'valable jusqu\'au' }}
                    {{ $member->temporary_password_expires_at->format('d/m/Y H:i') }}.
                    Le membre n'a pas encore choisi son mot de passe.
                </p>
            @endif

            <p class="mb-1">
                <strong>Appareils connectés :</strong> {{ $devicesCount }}
                @if ($lastUsedAt)
                    — dernière activité le {{ \Carbon\Carbon::parse($lastUsedAt)->format('d/m/Y H:i') }}
                @endif
            </p>
        @else
            <p class="text-muted mb-1">
                Le membre ne peut pas encore se connecter à l'application.
                L'activation génère un code provisoire à lui remettre en main propre.
            </p>
        @endif

        <div class="d-flex flex-wrap gap-2 pt-1">
            @if (! $member->mobile_activated_at)
                <button type="button" class="btn btn-primary btn-sm" wire:click="activate"
                    wire:confirm="Activer l'accès mobile de ce membre ? Un code provisoire va être généré."
                    wire:loading.attr="disabled">
                    Activer l'accès mobile
                </button>
            @else
                <button type="button" class="btn btn-outline-primary btn-sm" wire:click="activate"
                    wire:confirm="Générer un nouveau code provisoire ? L'ancien mot de passe ne fonctionnera plus et le membre sera déconnecté de tous ses appareils."
                    wire:loading.attr="disabled">
                    Nouveau code provisoire
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" wire:click="deactivate"
                    wire:confirm="Désactiver l'accès mobile ? Le membre sera déconnecté de tous ses appareils."
                    wire:loading.attr="disabled">
                    Désactiver
                </button>
            @endif
        </div>
    </div>

    {{-- Modale : le code provisoire n'est affiché qu'une seule fois --}}
    @if ($showCodeModal && $temporaryCode)
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);"
            aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Code provisoire du membre</h5>
                        <button type="button" class="btn-close" wire:click="closeCodeModal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-1">{{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</p>
                        <p class="mb-3">Identifiant : <strong>{{ $shortCode }}</strong></p>

                        <div class="display-5 fw-bold font-monospace border rounded py-3 mb-3"
                            style="letter-spacing: 0.3em;">
                            {{ $temporaryCode }}
                        </div>

                        <div class="alert alert-warning text-start small mb-0">
                            <ol class="mb-2 ps-3">
                                <li>Remettez ce code au membre en main propre, jamais par un tiers.</li>
                                <li>Dans l'application, il se connecte avec l'identifiant <strong>{{ $shortCode }}</strong> et ce code.</li>
                                <li>L'application l'oblige ensuite à choisir son propre mot de passe.</li>
                            </ol>
                            Ce code expire le
                            <strong>{{ $member->temporary_password_expires_at?->format('d/m/Y à H:i') }}</strong>
                            et <strong>ne sera plus affiché</strong> après fermeture de cette fenêtre.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="closeCodeModal">
                            J'ai remis le code au membre
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
