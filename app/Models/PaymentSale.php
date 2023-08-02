<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentSale extends Model
{
    use SoftDeletes;



    static $RECIVED = 1;
    static $CONFIRMED = 1;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'sale_id', 'date', 'montant', 'Ref','change', 'Reglement', 'user_id', 'notes','status','sales_settlement_id'
    ];

    protected $casts = [
        'montant' => 'double',
        'change'  => 'double',
        'sale_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function sale()
    {
        return $this->belongsTo('App\Models\Sale');
    }

    public function SalesSettlement()
    {
        return $this->belongsTo(SalesSettlement::class);
    }

}
