<?php

namespace App\Livewire\Members;

use App\Helpers\UserLogHelper;
use App\Models\User;
use App\Services\MobileAccessService;
use App\Support\MemberCode;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Bloc « Accès application mobile » affiché sur la fiche d'un membre.
 * Permet à l'agence d'activer l'accès, de remettre un nouveau code
 * provisoire (mot de passe oublié) ou de couper l'accès.
 */
class MobileAccess extends Component
{
    /** #[Locked] : le navigateur ne peut pas modifier cette valeur. */
    #[Locked]
    public int $memberId;

    /** Code provisoire en clair, affiché une seule fois dans la modale. */
    public ?string $temporaryCode = null;

    public bool $showCodeModal = false;

    public function mount(int $memberId): void
    {
        Gate::authorize('gerer-acces-mobile');
        $this->memberId = $memberId;
    }

    /**
     * Active l'accès, ou génère un nouveau code si l'accès est déjà actif.
     * La méthode reçoit le service par injection de dépendance.
     */
    public function activate(MobileAccessService $service): void
    {
        Gate::authorize('gerer-acces-mobile');

        $member = $this->member();
        $wasActive = $member->mobile_activated_at !== null;

        try {
            $code = $service->activate($member);
        } catch (DomainException $e) {
            notyf()->error($e->getMessage());

            return;
        }

        UserLogHelper::log_user_activity(
            action: $wasActive ? 'nouveau_code_acces_mobile' : 'activation_acces_mobile',
            description: ($wasActive ? 'Nouveau code provisoire mobile' : 'Activation de l\'accès mobile')
                . " pour {$member->name} {$member->postnom} ({$member->code})"
        );

        $this->temporaryCode = $code;
        $this->showCodeModal = true;
    }

    public function deactivate(MobileAccessService $service): void
    {
        Gate::authorize('gerer-acces-mobile');

        $member = $this->member();
        $service->deactivate($member);

        UserLogHelper::log_user_activity(
            action: 'desactivation_acces_mobile',
            description: "Désactivation de l'accès mobile pour {$member->name} {$member->postnom} ({$member->code})"
        );

        notyf()->success('Accès mobile désactivé. Tous les appareils du membre sont déconnectés.');
    }

    /**
     * Fermer la modale efface le code : il ne pourra plus être réaffiché.
     */
    public function closeCodeModal(): void
    {
        $this->temporaryCode = null;
        $this->showCodeModal = false;
    }

    private function member(): User
    {
        return User::where('role', 'membre')->findOrFail($this->memberId);
    }

    public function render()
    {
        $member = $this->member();

        return view('livewire.members.mobile-access', [
            'member' => $member,
            'shortCode' => MemberCode::short($member->code) ?? $member->code,
            'lastUsedAt' => $member->tokens()->max('last_used_at'),
            'devicesCount' => $member->tokens()->count(),
        ]);
    }
}
