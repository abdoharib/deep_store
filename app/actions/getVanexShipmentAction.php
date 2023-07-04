<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use App\Http\Controllers\SalesController;
use App\Models\Product;
use App\Models\product_warehouse;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\SaleStatusUpdateNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class getVanexShipmentAction
{

    public $token  = "136527|0YtNQw5nXBuJdvkaU1UqyfwpLgqImwFOJaipkNZC";
    public function invoke(Sale $sale) : array
    {

        // $store = Store::find(Auth::user()->store_id);

        // if(!$store->vanex_api_token){
        //     throw new VanexAPIShipmentException(['لم يتم تحديد رمز API للمتجر']);
        // }

        // $description = "";
        // foreach ($sale->details as $saleDetail) {
        //     $description = $description." ".$saleDetail->quantity." ".$saleDetail->product->name;
        // }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->get('https://app.vanex.ly/api/v1'. '/customer/package/'.$sale->vanex_shipment_code);
        $res_body = $response->body();
        $res_code = $response->status();


        if ($res_code != 200) {

            $error_arr = (isset($response['errors'])) ? $response['errors'] : ['خطا غير معروف '];
            array_push($error_arr, ' لم تتم العملية بنجاح نظراً لوجود خطا في  إضافة شحنة لنظام VANEX  : ');
            // throw new VanexAPIShipmentException($error_arr);
            dd($error_arr);
        } else {
            $package_details = $response->json('data');
            if(array_key_exists('status_object',$package_details)){
                $this->handleSaleStatusUpdate($sale,$package_details['status_object']);
            }
            $sale->save();
            return $package_details;
        }
    }

    public function handleSaleStatusUpdate(Sale $sale, array $vanex_status ){
        if (!array_key_exists('id',$vanex_status)) {
            return;
        }

        if (array_key_exists((int)$vanex_status['id'], $this->mapper)) {

            if($vanex_status['id'] == 1){
                $sale->update([
                    'shipping_status' => 'ordered'
                ]);

                $sale->shipment->update([
                    'status' => 'ordered'
                ]);

            }elseif($vanex_status['id'] == 16 || $vanex_status['id'] == 12 || $vanex_status['id'] == 10 ){
                $sale->update([
                    'shipping_status' => 'delivered'
                ]);

                $sale->shipment->update([
                    'status' => 'delivered'
                ]);

            }elseif($vanex_status['id'] == 2 || $vanex_status['id'] == 3){
                $sale->update([
                    'shipping_status' => 'cancelled'
                ]);

                $sale->shipment->update([
                    'status' => 'cancelled'
                ]);
            }elseif($vanex_status['id'] == 3){
                $sale->update([
                    'shipping_status' => 'returned'
                ]);

                $sale->shipment->update([
                    'status' => 'returned'
                ]);
            }else{
                $sale->update([
                    'shipping_status' => 'shipped'
                ]);

                $sale->shipment->update([
                    'status' => 'shipped'
                ]);
            }

            $this->updateSaleStatus($sale,$this->mapper[(int)$vanex_status['id']]);
        } else {

            $sale->update([
                'shipping_status' => 'shipped'
            ]);

            $sale->shipment->update([
                'status' => 'shipped'
            ]);

            $this->updateSaleStatus($sale,'under_shipping');
        }

    }

    public $mapper = [

        //شحنة ملغية
        2 => 'canceled',
        //شحنة مستردة
        3 => 'canceled',

        //شحنة تحت تسوية الشركة
        12 => 'completed',
        //شحنة قيد التسوية المالية
        10 => 'completed',
        //شحنة  مكتملة
        16 => 'completed',

        //شحنة قيد الأجراء
        1 => 'pending',
    ];

    public function updateSaleStatus(Sale $sale, string $status)  {

        $id = $sale->id;
        $sale->statut = $status;

        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $sale) {

            $current_Sale = Sale::findOrFail($id);

            if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
            }else{
                // Check If User Has Permission view All Records

                $old_sale_details = SaleDetail::where('sale_id', $id)->get();
                $new_sale_details = $old_sale_details->toArray();
                $length = sizeof($new_sale_details);

                // Get Ids for new Details
                $new_products_id = [];
                foreach ($new_sale_details as $new_detail) {
                    $new_products_id[] = $new_detail['id'];
                }

                // Init Data with old Parametre
                $old_products_id = [];
                foreach ($old_sale_details as $key => $value) {
                    $old_products_id[] = $value->id;

                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }

                    if($value['sale_unit_id'] !== null){
                        if ($current_Sale->statut == "completed") {

                            if ($value['product_variant_id'] !== null) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->where('product_variant_id', $value['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->first();
                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }
                                    $product_warehouse->save();
                                }
                            }
                        }
                        // Delete Detail
                        if (!in_array($old_products_id[$key], $new_products_id)) {
                            $SaleDetail = SaleDetail::findOrFail($value->id);
                            $SaleDetail->delete();
                        }
                    }
                }

                // Update Data with New request
                foreach ($new_sale_details as $prd => $prod_detail) {

                    //check if detail has sale_unit_id Or Null
                    if ($prod_detail['sale_unit_id'] !== null) {
                        $unit = Unit::where('id', $prod_detail['sale_unit_id'])->first();
                        $prod_detail['no_unit'] = 1;
                    } else {
                        $product_unit_sale_id = Product::with('unitSale')
                            ->where('id', $prod_detail['product_id'])
                            ->first();
                        $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                        $prod_detail['no_unit'] = 0;
                    }

                    if ($prod_detail['discount_method'] == '2') {
                        $prod_detail['DiscountNet'] = $prod_detail['discount'];
                    } else {
                        $prod_detail['DiscountNet'] = $prod_detail['price'] * $prod_detail['discount'] / 100;
                    }

                    $tax_price = $prod_detail['TaxNet'] * (($prod_detail['price'] - $prod_detail['DiscountNet']) / 100);

                    $prod_detail['Unit_price'] = $prod_detail['price'];
                    $prod_detail['tax_percent'] = $prod_detail['TaxNet'];
                    $prod_detail['tax_method'] = $prod_detail['tax_method'];
                    $prod_detail['discount'] = $prod_detail['discount'];
                    $prod_detail['discount_Method'] = $prod_detail['discount_method'];

                    if ($prod_detail['tax_method'] == '1') {
                        $prod_detail['Net_price'] = $prod_detail['price'] - $prod_detail['DiscountNet'];
                        $prod_detail['taxe'] = $prod_detail;
                        $prod_detail['subtotal'] = ($prod_detail['Net_price'] * $prod_detail['quantity']) + ($tax_price * $prod_detail['quantity']);
                    } else {
                        $prod_detail['Net_price'] = ($prod_detail['price'] - $prod_detail['DiscountNet']) / (($prod_detail['TaxNet'] / 100) + 1);
                        $prod_detail['taxe'] = $prod_detail['price'] - $prod_detail['Net_price'] - $prod_detail['DiscountNet'];
                        $prod_detail['subtotal'] = ($prod_detail['Net_price'] * $prod_detail['quantity']) + ($tax_price * $prod_detail['quantity']);
                    }



                    if($prod_detail['no_unit'] !== 0){
                        $unit_prod = Unit::where('id', $prod_detail['sale_unit_id'])->first();

                        if ($sale->statut == "completed") {

                            if ($prod_detail['product_variant_id'] !== null) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $sale->warehouse_id)
                                    ->where('product_id', $prod_detail['product_id'])
                                    ->where('product_variant_id', $prod_detail['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($unit_prod->operator == '/') {
                                        $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                    } else {
                                        $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                    }
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $sale->warehouse_id)
                                    ->where('product_id', $prod_detail['product_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($unit_prod->operator == '/') {
                                        $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                    } else {
                                        $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                    }
                                    $product_warehouse->save();
                                }
                            }

                        }

                        $orderDetails['sale_id'] = $id;
                        $orderDetails['date'] = $sale->date;
                        $orderDetails['price'] = $prod_detail['Unit_price'];
                        $orderDetails['sale_unit_id'] = $prod_detail['sale_unit_id'];
                        $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                        $orderDetails['tax_method'] = $prod_detail['tax_method'];
                        $orderDetails['discount'] = $prod_detail['discount'];
                        $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                        $orderDetails['quantity'] = $prod_detail['quantity'];
                        $orderDetails['product_id'] = $prod_detail['product_id'];
                        $orderDetails['product_variant_id'] = $prod_detail['product_variant_id'];
                        $orderDetails['total'] = $prod_detail['subtotal'];
                        $orderDetails['imei_number'] = $prod_detail['imei_number'];

                        if (!in_array($prod_detail['id'], $old_products_id)) {
                            $orderDetails['date'] = Carbon::now();
                            $orderDetails['sale_unit_id'] = $unit_prod ? $unit_prod->id : Null;
                            SaleDetail::Create($orderDetails);
                        } else {
                            SaleDetail::where('id', $prod_detail['id'])->update($orderDetails);
                        }
                    }
                }

                $due = $sale->GrandTotal - $current_Sale->paid_amount;
                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due != $sale->GrandTotal) {
                    $payment_statut = 'partial';
                } else if ($due == $sale->GrandTotal) {
                    $payment_statut = 'unpaid';
                }

                $old_status = $current_Sale->statut;


                $current_Sale->update([
                    'date' => $sale->date,
                    'postponed_date' => $sale->postponed_date,
                    'client_id' => $sale->client_id,
                    'warehouse_id' => $sale->warehouse_id,
                    'notes' => $sale->notes,
                    'statut' => $sale->statut,
                    'tax_rate' => $sale->tax_rate,
                    'TaxNet' => $sale->TaxNet,
                    'discount' => $sale->discount,
                    'shipping' => $sale->shipping,
                    'GrandTotal' => $sale->GrandTotal,
                    'payment_statut' => $payment_statut,
                ]);

                 //status updated
                 if($old_status !== $current_Sale->statut ){
                    $current_Sale->warehouse->assignedUsers->each(function(User $user) use($current_Sale){
                        $user->notify(new SaleStatusUpdateNotification($current_Sale));
                    });
                }
            }

        }, 10);
    }



}
