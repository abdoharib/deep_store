<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'treasury_id',
        'amount',
        'is_debit',
        'document_type',
        'document_id'
    ];


    public function treasury(){
        return $this->belongsTo(Treasury::class);
    }
}
