<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesSettlement extends Model
{
    use HasFactory;


    static $RECIVED = 'تم ألأستلام';
    static $CONFIRMED = "تم التصديق";


    protected $fillable = [
        'user_id',
        'courier',
        'amount_recived',
        'status',
        'no_sales',
        'date'
    ];


    public function paymentSales(){
        return $this->hasMany(PaymentSale::class);
    }
}
