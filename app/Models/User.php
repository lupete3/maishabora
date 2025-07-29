<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'code_manuel',
        'name',
        'postnom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'telephone',
        'adresse_physique',
        'province',
        'ville',
        'commune',
        'quartier',
        'profession',
        'type_piece',
        'numero_piece',
        'date_expiration_piece',
        'etat_civil',
        'nombre_dependants',
        'revenu_mensuel',
        'source_revenu',
        'nom_employeur',
        'nom_conjoint',
        'telephone_conjoint',
        'nom_reference',
        'telephone_reference',
        'lien_reference',
        'photo_profil',
        'scan_piece',
        'date_adhesion',
        'nationalite',
        'niveau_etude',
        'remarque',
        'email',
        'password',
        'role',
        'status'
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isComptable()
    {
        return $this->role === 'comptable';
    }

    public function isCaissier()
    {
        return $this->role === 'caissier';
    }

    public function isRecouvreur()
    {
        return $this->role === 'recouvreur';
    }

    public function isReceptionniste()
    {
        return $this->role === 'receptionniste';
    }

    public function isMembre()
    {
        return $this->role === 'membre';
    }
    public function isActive()
    {
        return $this->status == true;
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

    public function logs()
    {
        return $this->hasMany(UserLog::class);
    }


    protected static function booted()
    {
        static::creating(function ($user) {
            $exists = static::where('name', $user->name)
                ->where('postnom', $user->postnom)
                ->where('telephone', $user->telephone)
                ->exists();

            if ($exists) {
                throw new \Exception("Un membre avec le même nom, post-nom et numéro de téléphone existe déjà.");
            }
        });

        static::updating(function ($user) {
            $exists = static::where('id', '!=', $user->id)
                ->where('name', $user->name)
                ->where('postnom', $user->postnom)
                ->where('telephone', $user->telephone)
                ->exists();

            if ($exists) {
                notyf()->error("Un autre membre possède déjà ces informations (nom, post-nom, téléphone).");
            }
        });
    }
}
