<?php

namespace App\Livewire\Admin;

use App\Helpers\UserLogHelper;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Livewire\Component;
use Livewire\WithPagination;

class UserSessions extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'active';
    public $perPage = 15;

    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        abort_unless(auth()->user()?->can('afficher-logs'), 403);
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function terminateSession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            notyf()->error('Vous ne pouvez pas fermer votre propre session depuis cette page.');
            return;
        }

        $session = DB::table($this->sessionTable())->where('id', $sessionId)->first();

        if (!$session) {
            notyf()->error('Session introuvable.');
            return;
        }

        DB::table($this->sessionTable())->where('id', $sessionId)->delete();

        UserLogHelper::log_user_activity(
            'Déconnexion forcée',
            'Session utilisateur fermée par sécurité depuis la supervision des sessions.'
        );

        notyf()->success('La session a été fermée avec succès.');
    }

    public function terminateUserSessions(int $userId): void
    {
        if ($userId === auth()->id()) {
            notyf()->error('Vous ne pouvez pas fermer toutes vos propres sessions depuis cette page.');
            return;
        }

        $deleted = DB::table($this->sessionTable())
            ->where('user_id', $userId)
            ->delete();

        $user = User::find($userId);
        $name = $user ? trim($user->name . ' ' . $user->postnom . ' ' . $user->prenom) : 'Utilisateur #' . $userId;

        UserLogHelper::log_user_activity(
            'Déconnexion utilisateur',
            "{$deleted} session(s) fermée(s) pour {$name}."
        );

        notyf()->success("{$deleted} session(s) fermée(s).");
    }

    public function cleanExpiredSessions(): void
    {
        $deleted = DB::table($this->sessionTable())
            ->where('last_activity', '<', $this->activeCutoff())
            ->delete();

        UserLogHelper::log_user_activity(
            'Nettoyage sessions',
            "{$deleted} session(s) expirée(s) supprimée(s)."
        );

        notyf()->success("{$deleted} session(s) expirée(s) supprimée(s).");
    }

    private function sessionTable(): string
    {
        return config('session.table', 'sessions');
    }

    private function activeCutoff(): int
    {
        return now()->subMinutes((int) config('session.lifetime', 120))->timestamp;
    }

    private function onlineCutoff(): int
    {
        return now()->subMinutes(5)->timestamp;
    }

    private function baseQuery()
    {
        $query = DB::table($this->sessionTable() . ' as s')
            ->leftJoin('users as u', 's.user_id', '=', 'u.id')
            ->select([
                's.id',
                's.user_id',
                's.ip_address',
                's.user_agent',
                's.last_activity',
                'u.name',
                'u.postnom',
                'u.prenom',
                'u.email',
                'u.role',
                'u.status',
            ]);

        if ($this->statusFilter === 'active') {
            $query->where('s.last_activity', '>=', $this->activeCutoff());
        } elseif ($this->statusFilter === 'online') {
            $query->where('s.last_activity', '>=', $this->onlineCutoff());
        } elseif ($this->statusFilter === 'expired') {
            $query->where('s.last_activity', '<', $this->activeCutoff());
        }

        if (trim($this->search) !== '') {
            $search = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', $search)
                    ->orWhere('u.postnom', 'like', $search)
                    ->orWhere('u.prenom', 'like', $search)
                    ->orWhere('u.email', 'like', $search)
                    ->orWhere('u.role', 'like', $search)
                    ->orWhere('s.ip_address', 'like', $search);
            });
        }

        return $query;
    }

    private function stats(): array
    {
        $table = $this->sessionTable();

        return [
            'total_sessions' => DB::table($table)->count(),
            'active_sessions' => DB::table($table)->where('last_activity', '>=', $this->activeCutoff())->count(),
            'online_sessions' => DB::table($table)->where('last_activity', '>=', $this->onlineCutoff())->count(),
            'connected_users' => DB::table($table)
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $this->activeCutoff())
                ->distinct('user_id')
                ->count('user_id'),
            'guest_sessions' => DB::table($table)
                ->whereNull('user_id')
                ->where('last_activity', '>=', $this->activeCutoff())
                ->count(),
            'expired_sessions' => DB::table($table)->where('last_activity', '<', $this->activeCutoff())->count(),
        ];
    }

    private function formatSession($session)
    {
        $lastActivity = Carbon::createFromTimestamp($session->last_activity);
        $fullName = trim(($session->name ?? '') . ' ' . ($session->postnom ?? '') . ' ' . ($session->prenom ?? ''));

        $session->full_name = $fullName !== '' ? $fullName : 'Invité / session anonyme';
        $session->short_id = Str::limit($session->id, 14, '');
        $session->is_current = $session->id === session()->getId();
        $session->is_active = $session->last_activity >= $this->activeCutoff();
        $session->is_online = $session->last_activity >= $this->onlineCutoff();
        $session->last_seen = $lastActivity->format('d/m/Y H:i');
        $session->idle_for = $lastActivity->diffForHumans();
        $session->device = $this->describeUserAgent($session->user_agent);

        return $session;
    }

    private function describeUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Appareil inconnu';
        }

        $agent = new Agent();
        $agent->setUserAgent($userAgent);

        $platformName = $agent->platform();
        $browserName = $agent->browser();
        $platform = trim($platformName . ' ' . ($platformName ? $agent->version($platformName) : ''));
        $browser = trim($browserName . ' ' . ($browserName ? $agent->version($browserName) : ''));
        $device = $agent->isMobile() ? 'Mobile' : ($agent->isTablet() ? 'Tablette' : 'Ordinateur');

        return trim($device . ' - ' . ($platform ?: 'OS inconnu') . ' - ' . ($browser ?: 'Navigateur inconnu'));
    }

    public function render()
    {
        $sessions = $this->baseQuery()
            ->orderByDesc('s.last_activity')
            ->paginate($this->perPage);

        $sessions->setCollection(
            $sessions->getCollection()->map(fn($session) => $this->formatSession($session))
        );

        return view('livewire.admin.user-sessions', [
            'sessions' => $sessions,
            'stats' => $this->stats(),
        ]);
    }
}
