<?php

namespace App\actions;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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

                try {
                    $package_details = $this->getVanexShipmentAction->invoke($sale);
                } catch (\Exception $th) {
                    Log::debug($th->getMessage());
                }
                Log::debug("updated sale".$sale->Ref);

                if (!is_null($package_details)) {
                    $sale->update([
                        'vanex_shipment_status' => $package_details['status_object']['status_name_cust'],
                        'last_vanex_update' => now()
                    ]);
                }
                $sales_updated++;
            }
        }
    }
}
