<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_operation',
        'libelle',
        'reference',
        'devise',
        'montant_debit',
        'montant_credit',
        'type_operation',
        'compte_id',
        'type_journal_id',
        'user_id',
    ];

    protected $casts = [
        'montant_debit'  => 'float',
        'montant_credit' => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function journalType()
    {
        return $this->belongsTo(JournalType::class, 'type_journal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
