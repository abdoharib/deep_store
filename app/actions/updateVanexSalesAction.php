<?php

namespace App\actions;

use App\Models\Sale;
use Carbon\Carbon;

class updateVanexSalesAction
{

    public $getVanexShipmentAction = null;

    public function __construct(getVanexShipmentAction $getVanexShipmentAction) {
        $this->getVanexShipmentAction = $getVanexShipmentAction;
    }

    public function invoke(){
        $sales = Sale::with('facture', 'client', 'warehouse','user')->where('deleted_at', '=', null)->get();
        $sales_updated = 0;

        foreach ($sales as $sale) {

            $package_details = null;
            //has vanex code
            if ($sale->shipping_provider == 'vanex' && $sale->vanex_shipment_code) {
                //retrive the shipping info

                $package_details = $this->getVanexShipmentAction->invoke($sale);

                if (!is_null($package_details)) {
                    $sale->update([
                        'vanex_shipment_status' => $package_details['status_object']['status_name_cust']
                    ]);
                }
                $sales_updated++;
            }
        }
    }
}
