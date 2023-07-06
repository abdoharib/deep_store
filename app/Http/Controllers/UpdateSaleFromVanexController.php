<?php

namespace App\Http\Controllers;

use App\actions\getVanexShipmentAction;
use App\Models\Role;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;

class UpdateSaleFromVanexController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @var \Illuminate\Database\Eloquent\Builder $sale
     */
    public function __invoke(request $request, getVanexShipmentAction $getVanexShipmentAction)
    {



        try {


            $sales = Sale::with('facture', 'client', 'warehouse','user')->where('deleted_at', '=', null)->get();
            $sales_updated = 0;

            foreach ($sales as $sale) {

                $package_details = null;
                //has vanex code
                if ($sale->shipping_provider == 'vanex' && $sale->vanex_shipment_code) {
                    //retrive the shipping info

                    $package_details = $getVanexShipmentAction->invoke($sale);

                    if (!is_null($package_details)) {
                        $sale->update([
                            'vanex_shipment_status' => $package_details['status_object']['status_name_cust']
                        ]);
                    }
                    $sales_updated++;
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'sales_updated' => $sales_updated,
                'status' => 500,
            ]);
        }




        return response()->json([
            'message' => 'Successfully updated Vanex Sales',
            'sales_updated' => $sales_updated,
            'status' => 200,
        ]);
    }
}
