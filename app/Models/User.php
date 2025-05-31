<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'postnom',
        'prenom',
        'date_naissance',
        'telephone',
        'adresse_physique',
        'profession',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function membershipCards()
    {
        return $this->hasMany(MembershipCard::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCaissier()
    {
        return $this->role === 'caissier';
    }

    public function isRecouvreur()
    {
        return $this->role === 'recouvreur';
    }

    public function isMembre()
    {
        return $this->role === 'membre';
    }

    public function isMembres()
    {
        return $this->role === 'membr';
    }

    // Relations
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function agentAccounts()
    {
        return $this->hasMany(AgentAccount::class);
    }

    public function agentAccount()
    {
        return $this->hasOne(AgentAccount::class);
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
