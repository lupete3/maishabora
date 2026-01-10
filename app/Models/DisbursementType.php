<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisbursementType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function disbursements()
    {
        return $this->hasMany(Transaction::class, 'disbursement_type_id');
    }
}
