<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Treasury extends Model
{

    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'name',
        'balance',
    ];




    public function salesPayments(){
        return $this->hasMany(PaymentSale::class);
    }


    public function PurchasesPayments(){
        return $this->hasMany(PaymentPurchase::class);
    }


    public function expenses(){
        return $this->hasMany(Expense::class);
    }



}
