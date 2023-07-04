<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shipment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class getVanexStorageProduct
{

    public $bengazi_account_token  = "141297|EOYQwmzYvSZsLbXvFk6ryvAIfS7xZOzYktOe6ztm";
    public function invoke(Product $product, int $qty)
    {

        // $store = Store::find(Auth::user()->store_id);

        // if(!$store->vanex_api_token){
        //     throw new VanexAPIShipmentException(['لم يتم تحديد رمز API للمتجر']);
        // }

        // $description = "";
        // foreach ($sale->details as $saleDetail) {
        //     $description = $description." ".$saleDetail->quantity." ".$saleDetail->product->name;
        // }


        if( trim($product->vanex_storage_product_ref_id) == '' ){
            throw new \Exception('المنتج ليس متصل بي منتج مخزون فانكس');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->bengazi_account_token,
        ])->get('https://app.vanex.ly/api/v1'. '/safe-storage/products/'.$product->vanex_storage_product_ref_id);
        $res_body = $response->body();
        $res_code = $response->status();

        if ($res_code != 200) {
            $error_arr = (isset($response['errors'])) ? $response['errors'] : ['خطا غير معروف '];
            array_push($error_arr, ' لم تتم العملية بنجاح نظراً لوجود خطا في جلب بيانات المنتج VANEX  : ');
            // throw new VanexAPIShipmentException($error_arr);
            throw new \Exception(implode(' ',$error_arr));
        } else {
            $data = $response->json('data');
            if(array_key_exists('total_qty',$data)){

                if((int)$data['total_qty'] >= $qty){

                    $data['qty'] = $qty;
                    $data['total_price'] = (int)$qty * (float)$data['unit_price'];

                    return $data;
                }else{
                    throw new \Exception(' لا يوجد كمية كافية للمنتج '.$product->name. ",متوفر".$data['total_qty'].'قطع فقط');
                }

            }else{
                throw new \Exception('حدث خطأ في جلب البيانات');
            }

        }
    }
}
