<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalType extends Model
{
    use HasFactory;

    protected $fillable = ['libelle'];

    public function journals()
    {
        return $this->hasMany(Journal::class, 'type_journal_id');
    }
}
