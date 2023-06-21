<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use App\Models\Sale;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class createVanexShipmentAction
{

    public $token  = "136527|0YtNQw5nXBuJdvkaU1UqyfwpLgqImwFOJaipkNZC";
    public function invoke(Sale $sale)
    {

        // $store = Store::find(Auth::user()->store_id);

        // if(!$store->vanex_api_token){
        //     throw new VanexAPIShipmentException(['لم يتم تحديد رمز API للمتجر']);
        // }

        $description = "";
        foreach ($sale->details as $saleDetail) {
            $description = $description." ".$saleDetail->quantity." ".$saleDetail->product->name;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->post('https://app.vanex.ly/api/v1'. '/customer/package', [
            'reciever' => 'زبون',
            'store_sub_sender' => null,
            'store_reference_id' => $sale->id,
            'store_pkg_details' => json_encode($sale->details),
            'qty' => $sale->details->count(),
            'phone' => $sale->client->phone,
            'phone_b' => $sale->client->phone,
            'price' => $sale->GrandTotal,
            'sticker_notes' => $sale->vanex_shipment_sticker_notes,
            'description' => $description ,
            'height' => '35',
            'leangh' => '35',
            'width' => '35',
            'city' => $sale->vanex_city_id,
            'address_child' => $sale->vanex_sub_city_id,
            'payment_methode' => 'cash',
            'paid_by' => 'customer',
            'extra_size_by' => 'customer',
            'commission_by' => 'customer',
            'type' => '1',
            'address' => 'تنسيق مع الزبون'
        ]);
        $res_body = $response->body();
        $res_code = $response->status();

        if ($res_code != 201) {
            $error_arr = (isset($response['errors'])) ? $response['errors'] : ['خطا غير معروف '];
            array_push($error_arr, ' لم تتم العملية بنجاح نظراً لوجود خطا في  إضافة شحنة لنظام VANEX  : ');
            // throw new VanexAPIShipmentException($error_arr);
            dd($error_arr);
        } else {
            $vanex_package_code = $response->json('package_code');
            $sale->vanex_shipment_code = $vanex_package_code;

            \Illuminate\Support\Facades\DB::transaction(function () use($sale) {
                $shipment = Shipment::make();

                $shipment->user_id = Auth::user()->id;
                $shipment->sale_id = $sale->id;
                $shipment->delivered_to = 'Vanex';
                $shipment->shipping_address = '';
                $shipment->shipping_details = '';
                $shipment->status = 'ordered';
                $shipment->save();
                $sale->shipping_status = 'ordered';
            }, 10);
            $sale->save();
        }
    }
}
