<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use App\Models\Sale;
use App\Models\Shipment;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class createVanexShipmentAction
{

    public $bengazi_account_token  = "141297|EOYQwmzYvSZsLbXvFk6ryvAIfS7xZOzYktOe6ztm";
    public $tripoli_token  = env('VANEX_TOKEN');
    public $token = null;
    public $getVanexStorageProduct = null;


    public function __construct(getVanexStorageProduct $getVanexStorageProduct) {
        $this->getVanexStorageProduct = $getVanexStorageProduct;
    }

    public function invoke(Sale $sale)
    {

        $token = null;
        if(tenant('id') == 1){
            if ($sale->warehouse->id == 1) {
                $token = $this->tripoli_token;
            } elseif ($sale->warehouse->id == 6) {
                $token = $this->bengazi_account_token;
            } else {
                throw new \Exception('المستودع غير مدعوم');
            }
        }else{
            $token = $this->tripoli_token;
        }

       $total_amount = 0;
       $total_qty = 0;

       $products = [];
       if($sale->warehouse->id == 6 && (tenant('id') == 1)){
        foreach ($sale->details as $detail) {
            $product = $this->getVanexStorageProduct->invoke($detail->product,$detail->quantity);
            $products[] = $product;
            $total_amount = $total_amount + $product['total_price'];
            $total_qty = $total_qty + $product['qty'];
        }

        $total_amount = $total_amount- $sale->discount;

       }else{
        $total_amount = $sale->GrandTotal;
        $total_qty = $sale->details->sum('quantity');
       }




        // $store = Store::find(Auth::user()->store_id);

        // if(!$store->vanex_api_token){
        //     throw new VanexAPIShipmentException(['لم يتم تحديد رمز API للمتجر']);
        // }

        $description = "";
        foreach ($sale->details as $saleDetail) {
            $description = $description." ".$saleDetail->quantity." ".$saleDetail->product->name;
        }
        $description = $description." ".$sale->notes;

        $payload = [
            'reciever' => 'زبون',
            'store_sub_sender' => null,
            'store_reference_id' => $sale->id,
            'store_pkg_details' => json_encode($sale->details),
            'qty' => $total_qty,
            'phone' => $sale->client->phone,
            'phone_b' => $sale->client->phone,
            'price' => $total_amount,
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
            'address' => 'تنسيق مع الزبون',
            'type_id'=> 1,
            'package_sub_type' => 6
        ];


        if(count($products)){
            $payload['products'] = json_encode($products);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post('https://app.vanex.ly/api/v1'. '/customer/package', $payload);
        $res_body = $response->body();
        $res_code = $response->status();
        // dd($res_code);

        if (((int)$res_code != 200) && ((int)$res_code != 201)) {
            // dd($res_code);
            // dd(($res_code != 201));
            dd($res_body);
            // $error_arr = (isset($response['errors'])) ? $response['errors'] : ['خطا غير معروف '];
            // array_push($error_arr, ' لم تتم العملية بنجاح نظراً لوجود خطا في  إضافة شحنة لنظام VANEX  : ');
            // throw new VanexAPIShipmentException($error_arr);
            throw new Exception('لم تتم العملية بنجاح نظراً لوجود خطا في  إضافة شحنة لنظام VANEX  : ');
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
