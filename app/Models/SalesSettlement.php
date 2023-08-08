<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SalesSettlement extends Model
{
    use BelongsToTenant;

    use HasFactory;


    static $RECIVED = 'تم ألأستلام';
    static $CONFIRMED = "تم التصديق";


    protected $fillable = [
        'user_id',
        'courier',
        'amount_recived',
        'status',
        'no_sales',
        'note',
        'date'
    ];


    public function paymentSales(){
        return $this->hasMany(PaymentSale::class);
    }
}
