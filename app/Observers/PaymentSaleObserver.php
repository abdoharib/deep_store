<?php

namespace App\Observers;

use App\Models\PaymentSale;
use App\Models\SalesSettlement;
use App\Models\Treasury;

class PaymentSaleObserver
{
    /**
     * Handle the PaymentSale "created" event.
     *
     * @param  \App\Models\PaymentSale  $paymentSale
     * @return void
     */
    public function created(PaymentSale $paymentSale)
    {
        // if($paymentSale->status == SalesSettlement::$CONFIRMED){
        //     Treasury::first()->increment('balance',$paymentSale->montant);
        // }
    }

    /**
     * Handle the PaymentSale "updated" event.
     *
     * @param  \App\Models\PaymentSale  $paymentSale
     * @return void
     */
    public function updated(PaymentSale $paymentSale)
    {

    }

    /**
     * Handle the PaymentSale "deleted" event.
     *
     * @param  \App\Models\PaymentSale  $paymentSale
     * @return void
     */
    public function deleted(PaymentSale $paymentSale)
    {
        //
    }

    /**
     * Handle the PaymentSale "restored" event.
     *
     * @param  \App\Models\PaymentSale  $paymentSale
     * @return void
     */
    public function restored(PaymentSale $paymentSale)
    {
        //
    }

    /**
     * Handle the PaymentSale "force deleted" event.
     *
     * @param  \App\Models\PaymentSale  $paymentSale
     * @return void
     */
    public function forceDeleted(PaymentSale $paymentSale)
    {
        //
    }
}
