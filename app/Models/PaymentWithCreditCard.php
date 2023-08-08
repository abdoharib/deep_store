<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PaymentWithCreditCard extends Model
{

    use BelongsToTenant;

    protected $table = 'payment_with_credit_card';

    protected $fillable = [
        'payment_id', 'customer_id', 'customer_stripe_id', 'charge_id',
    ];

    protected $casts = [
        'payment_id' => 'integer',
        'customer_id' => 'integer',
    ];


}
