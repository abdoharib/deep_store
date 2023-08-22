<?php

namespace App\actions;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class updateVanexSalesAction
{

    public $getVanexShipmentAction = null;

    public function __construct(getVanexShipmentAction $getVanexShipmentAction)
    {
        $this->getVanexShipmentAction = $getVanexShipmentAction;
    }

    public function invoke()
    {

        $shipments = $this->getVanexShipmentAction->getAllVanexShipments();
        Log::debug('retived ' . count($shipments) . ' shipment');
        foreach ($shipments as $shipment) {
            $package_code = explode('-', $shipment['package-code']);
            array_shift($package_code);
            $package_code = implode('-', $package_code);
            $shipment['package-code'] = $package_code;
            // array_splice()
            try {

                Log::debug("Trying to Update " . $shipment['package-code']);
                //code...


                $sale = Sale::query()
                    ->where('vanex_shipment_code', $shipment['package-code'])
                    ->first();

                if ($sale) {

                    Log::debug("Updating Sale Status 🔃");

                    $this->getVanexShipmentAction->handleSaleStatusUpdate($sale, $shipment['status_object']);
                    $sale->save();
                    // Log::debug("Upadated Sale Status");

                    $sale->update([
                        'vanex_shipment_status' => $shipment['status_object']['status_name_cust'],
                        'last_vanex_update' => now()->toDateTimeString()
                    ]);
                    Log::debug("Updated Sale" . $sale->Ref . ' ✅');
                } else {
                    Log::debug('Sale Was Not Found ❌');
                }
            } catch (\Throwable $th) {
                //throw $th;
                Log::debug($th->getMessage());
            }
        }

        // $sales = Sale::with('facture', 'client', 'warehouse','user')->where('deleted_at', '=', null)->orderBy('id','desc')->get();
        // $sales_updated = 0;

        // foreach ($sales as $sale) {

        //     $package_details = null;
        //     //has vanex code
        //     if ($sale->shipping_provider == 'vanex' && $sale->vanex_shipment_code) {
        //         //retrive the shipping info

        //         try {
        //             sleep(2);
        //             $package_details = $this->getVanexShipmentAction->invoke($sale);

        //             Log::debug("updated sale".$sale->Ref);

        //         if (!is_null($package_details)) {
        //             $sale->update([
        //                 'vanex_shipment_status' => $package_details['status_object']['status_name_cust'],
        //                 'last_vanex_update' => now()->toDateTimeString()
        //             ]);
        //         }
        //         $sales_updated++;
        //         } catch (\Exception $th) {
        //             Log::debug($th->getMessage());
        //         }


        //     }
        // }
    }
}
