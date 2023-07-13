<?php

namespace App\actions;

use App\Models\Ad;
use App\Models\AdWarehouse;
use App\Models\Product;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;

class updateAdsAction
{

    private $getRunningAdsAction;

    public function __construct(getRunningAdsAction $getRunningAdsAction)
    {
        $this->getRunningAdsAction = $getRunningAdsAction;
    }

    public function invoke()
    {


        $ads_data = $this->getRunningAdsAction->invoke();
        foreach ($ads_data as $ad_data) {

            $ad = Ad::where('ad_ref_id',$ad_data['id'])->first();
            if(!Product::find($ad_data['product_id'])){
                break;
            }

            $no_sales = SaleDetail::where('product_id',$ad_data['product_id'])
            ->whereHas('sale',function($q)use($ad_data){

                $q
                ->where(function($q) use($ad_data) {
                    if(array_key_exists('adset',$ad_data)){
                        if(array_key_exists('end_time',$ad_data['adset'])){
                            $q->whereDate('date','<=',Carbon::make($ad_data['adset']['end_time'])->toDateString());
                        }
                    }
                })
                ->where(function($q) use($ad_data) {
                    if(array_key_exists('adset',$ad_data)){
                        if(array_key_exists('start_time',$ad_data['adset'])){
                            $q->whereDate('date','>=',Carbon::make($ad_data['adset']['start_time'])->toDateString());
                        }
                    }
                })
                ->whereIn('warehouse_id',$ad_data['warehouse_id']);

            })->get()->sum('quantity');


            $completed_sales = SaleDetail::where('product_id',$ad_data['product_id'])
            ->whereHas('sale',function($q)use($ad_data){

                $q->whereDate('date','>=',Carbon::make($ad_data['adset']['start_time'])->toDateString())
                ->where(function($q) use($ad_data) {
                    if(array_key_exists('adset',$ad_data)){
                        if(array_key_exists('end_time',$ad_data['adset'])){
                            $q->whereDate('date','<=',Carbon::make($ad_data['adset']['end_time'])->toDateString());
                        }
                    }
                })
                ->where(function($q) use($ad_data) {
                    if(array_key_exists('adset',$ad_data)){
                        if(array_key_exists('start_time',$ad_data['adset'])){
                            $q->whereDate('date','>=',Carbon::make($ad_data['adset']['start_time'])->toDateString());
                        }
                    }
                })
                ->whereIn('warehouse_id',$ad_data['warehouse_id'])

                ->where('statut','completed');

            })->get();

            $no_completed_sales = $completed_sales->sum('quantity');

            $completed_sales_profit = 0;
            $product = Product::where('id',$ad_data['product_id'])->first();

            if($product){
                $completed_sales_profit = ($no_completed_sales * $product->profit) - $completed_sales->pluck('sale')->sum('discount');
            }

            if($ad){

                $end_time = array_key_exists('end_time',$ad_data['adset']) ?
                SupportCarbon::make($ad_data['adset']['end_time'])->toDateTimeString()
                :null;
            //update existing one
                $ad->update([
                    'ad_ref_status' => $ad_data['status'],
                    'ad_set_ref_status' => $ad_data['adset']['status'],
                    'last_ad_update_at' => now()->toDateTimeString(),
                    'ad_ref_effective_status' => $ad_data['effective_status'],
                    'amount_spent' => ($ad_data['total_spent'] * 5),
                    'start_date' => SupportCarbon::make($ad_data['adset']['start_time'])->toDateTimeString(),
                    'end_date' => $end_time,
                    'product_id' => $ad_data['product_id'],
                    'product_name' => '',
                    'no_sales' => $no_sales,
                    'no_completed_sales' => $no_completed_sales,
                    'completed_sales_profit' => $completed_sales_profit,

                ]);

                //delete all
                $ad->warehouses()->delete();

                foreach ($ad_data['warehouse_id'] as $ad_warehouse_id) {

                    AdWarehouse::create([
                        'ad_id' => $ad->id,
                        'warehouse_id' => $ad_warehouse_id
                     ]);

                }

                Log::debug('ad updated');

            }else{

                $end_time = array_key_exists('end_time',$ad_data['adset']) ?
                SupportCarbon::make($ad_data['adset']['end_time'])->toDateTimeString()
                :null;
                //create new one
                $ad = Ad::create([
                    'ad_ref_id' => $ad_data['id'],
                    'ad_ref_status' => $ad_data['status'],
                    'ad_set_ref_status' => $ad_data['adset']['status'],
                    "last_ad_update_at" => now()->toDateTimeString(),
                    'product_id' => $ad_data['product_id'],
                    'ad_ref_effective_status' => $ad_data['effective_status'],
                    'product_name' => '',

                    'amount_spent' => ($ad_data['total_spent'] * 5),

                    'start_date' => $ad_data['adset']['start_time'],
                    'end_date' => $end_time,
                    'no_sales' => $no_sales,
                    'no_completed_sales' => $no_completed_sales,
                    'completed_sales_profit' => $completed_sales_profit,
                ]);

                foreach ($ad_data['warehouse_id'] as $ad_warehouse_id) {

                    AdWarehouse::create([
                       'ad_id' => $ad->id,
                       'warehouse_id' => $ad_warehouse_id
                    ]);

                }

                Log::debug('ad created');

            }
        }

    }
}
