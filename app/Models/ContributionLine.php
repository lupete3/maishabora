<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContributionLine extends Model
{
    /** @use HasFactory<\Database\Factories\ContributionLineFactory> */
    use HasFactory;

    protected $fillable = [
        'contribution_book_id',
        'numero_ligne',
        'date_contribution',
        'montant'
    ];

    public function book()
    {
        return $this->belongsTo(ContributionBook::class);
    }

    // CashRegister.php
    public function reference()
    {
        return $this->morphTo();
    }

    public function contributionBook()
    {
        return $this->belongsTo(ContributionBook::class, 'contribution_book_id');
    }
}
