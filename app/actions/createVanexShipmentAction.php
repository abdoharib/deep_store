<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class createVanexShipmentAction
{

    public function invoke()
    {

        $store = Store::find(Auth::user()->store_id);

        if(!$store->vanex_api_token){
            throw new VanexAPIShipmentException(['لم يتم تحديد رمز API للمتجر']);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $store->vanex_api_token
        ])->post(env('VNX_API_URL') . '/customer/package?from-mobile=1', [
            'reciever' => $sale->customer->first_name . ' ' . $sale->customer->last_name,
            'store_sub_sender' => $store->id,
            'store_reference_id' => $sale->id,
            'store_pkg_details' => json_encode($sale->sales_items),
            'qty' => $sale->qty,
            'phone' => $sale->customer->phone,
            'phone_b' => $sale->customer->phone,
            'price' => $sale->total_price,
            'sticker_notes' => '',
            'description' => '',
            'height' => '35',
            'leangh' => '35',
            'width' => '35',
            'city' => $sale->customer->city_id,
            'address_child' => $sale->customer->sub_city_id,
            'payment_methode' => 'cash',
            'paid_by' => 'customer',
            'extra_size_by' => 'customer',
            'commission_by' => 'customer',
            'type' => '1',
            'address' => $sale->customer->address
        ]);
        $res_body = $response->body();
        $res_code = $response->status();

        if ($res_code != 201) {
            $error_arr = (isset($response['errors'])) ? $response['errors'] : ['خطا غير معروف '];
            array_push($error_arr, ' لم تتم العملية بنجاح نظراً لوجود خطا في  إضافة شحنة لنظام VANEX  : ');
            throw new VanexAPIShipmentException($error_arr);
        } else {
            $vanex_package_code = $response->json('package_code');
            $sale->vanex_package_code =   $vanex_package_code;
            $sale->save();
        }
    }
}
