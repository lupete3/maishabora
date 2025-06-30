<?php

namespace App\Livewire\Receptionist;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ReceptionistDashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        Gate::authorize('afficher-tableaudebord-receptionist', User::class);


        // Tous les clients dans le systeme
        $totalUsers = User::where('role', 'membre')->count();
        $totalUsersActifs = User::where('role', 'membre')->where('status', true)->count();
        $totalUsersInactifs = User::where('role', 'membre')->where('status', false)->count();

        return view('livewire.receptionist.receptionist-dashboard', compact(
            'totalUsers','totalUsersActifs','totalUsersInactifs'));
    }
}
