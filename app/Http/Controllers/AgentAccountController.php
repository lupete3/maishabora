<?php

namespace App\Http\Controllers;

use App\Models\AgentAccount;
use App\Models\User;
use Illuminate\Http\Request;

class AgentAccountController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('status', 'all');
        if (!in_array($filter, ['all', 'active', 'inactive'], true)) {
            $filter = 'all';
        }

        $search = trim((string) $request->query('search', ''));
        $agents = User::query()
            ->whereHas('agentAccounts')
            ->with('agentAccounts:id,user_id,currency,balance,is_visible_dashboard')
            ->when($filter === 'active', fn ($query) => $query->where('status', true))
            ->when($filter === 'inactive', fn ($query) => $query->where('status', false))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('postnom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('telephone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $agentUsers = User::query()->whereHas('agentAccounts');
        $counts = [
            'all' => (clone $agentUsers)->count(),
            'active' => (clone $agentUsers)->where('status', true)->count(),
            'inactive' => (clone $agentUsers)->where('status', false)->count(),
        ];

        return view('agent-accounts.index', compact('agents', 'counts', 'filter', 'search'));
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        abort_unless($user->agentAccounts()->exists(), 404);

        $active = (bool) $validated['status'];
        if (!$active && $user->is(auth()->user())) {
            notyf()->error('Vous ne pouvez pas désactiver votre propre compte.');
            return back();
        }

        $user->status = $active;
        $user->save();

        notyf()->success($active ? 'Le compte agent a été activé.' : 'Le compte agent a été désactivé.');
        return back();
    }
}
