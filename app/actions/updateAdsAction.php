<?php

namespace App\actions;

use App\Models\Ad;
use App\Models\AdWarehouse;
use App\Models\Cycle;
use App\Models\CycleVersion;
use App\Models\Product;
use App\Models\SaleDetail;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\ErrorHandler\Debug;

use function Psy\debug;

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

            $lifetime_budget = 0;
            if(array_key_exists('lifetime_budget',$ad_data['adset'])){
                $lifetime_budget = ((double)$ad_data['adset']['lifetime_budget']/100)*5;
            }

            $stop_time = null;
            $start_time = null;

            if(array_key_exists('start_time', $ad_data['campaign'])){
                $start_time = $ad_data['campaign']['start_time'];
            }
            if(array_key_exists('stop_time', $ad_data['campaign'])){
                $stop_time = $ad_data['campaign']['stop_time'];
            }


                $ad->update([

                    'campaing_ref_id' => $ad_data['campaign']['id'],
                    'campaign_name' => $ad_data['campaign']['name'],

                    'campaing_start_date' => $start_time ? SupportCarbon::make($start_time)->toDateTimeString() : null,
                    'campaing_end_date' => $stop_time ? SupportCarbon::make($stop_time)->toDateTimeString(): null,

                    'running_status' => $this->getRunningStatus($ad),
                    'preformance_status' => App::make(getAdPreformanceStatusAction::class)->invoke($ad),
                    'growth_status' => $this->getGrowthData($ad),

                    'ad_ref_status' => $ad_data['status'],
                    'ad_set_ref_id' => $ad_data['adset']['id'],
                    'ad_set_ref_status' => $ad_data['adset']['status'],
                    'last_ad_update_at' => now()->toDateTimeString(),
                    'ad_ref_effective_status' => $ad_data['effective_status'],
                    'lifetime_budget' => $lifetime_budget,
                    'amount_spent' => ($ad_data['total_spent'] * 5),
                    'cost_per_message' => $ad_data['cost_per_message'] * 5,
                    'start_date' => SupportCarbon::make($ad_data['adset']['start_time'])->toDateTimeString(),
                    'end_date' => $end_time ? SupportCarbon::make($end_time)->toDateTimeString() : null,
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

                $lifetime_budget = 0;
                if(array_key_exists('lifetime_budget',$ad_data['adset'])){
                    $lifetime_budget = ((double)$ad_data['adset']['lifetime_budget']/100)*5;
                }

                $stop_time = null;
                $start_time = null;

                if(array_key_exists('start_time', $ad_data['campaign'])){
                    $start_time = $ad_data['campaign']['start_time'];
                }
                if(array_key_exists('stop_time', $ad_data['campaign'])){
                    $stop_time = $ad_data['campaign']['stop_time'];
                }

                $ad = Ad::create([



                    'campaing_ref_id' => $ad_data['campaign']['id'],
                    'campaign_name' => $ad_data['campaign']['name'],

                    'campaing_start_date' => $start_time ? SupportCarbon::make($start_time)->toDateTimeString() : null,
                    'campaing_end_date' => $stop_time ? SupportCarbon::make($stop_time)->toDateTimeString(): null,

                    'ad_ref_id' => $ad_data['id'],
                    'ad_set_ref_id' => $ad_data['adset']['id'],
                    'lifetime_budget' => $lifetime_budget,
                    'ad_ref_status' => $ad_data['status'],
                    'ad_set_ref_status' => $ad_data['adset']['status'],
                    "last_ad_update_at" => now()->toDateTimeString(),
                    'product_id' => $ad_data['product_id'],
                    'ad_ref_effective_status' => $ad_data['effective_status'],
                    'product_name' => '',

                    'amount_spent' => ($ad_data['total_spent'] * 5),
                    'cost_per_message' => $ad_data['cost_per_message'] * 5,

                    'start_date' => SupportCarbon::make($ad_data['adset']['start_time'])->toDateTimeString(),
                    'end_date' => $end_time ? SupportCarbon::make($end_time)->toDateTimeString() : null,

                    'no_sales' => $no_sales,
                    'no_completed_sales' => $no_completed_sales,
                    'completed_sales_profit' => $completed_sales_profit,
                ]);




                $ad->update([
                    'running_status' => $this->getRunningStatus($ad),
                    'preformance_status' => App::make(getAdPreformanceStatusAction::class)->invoke($ad),
                    'growth_status' => $this->getGrowthData($ad)
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

        Product::all()->each(function($product){
            $ad = $product->ads()->orderBy('start_date','desc')->first();

            if($ad){
                $ad->update([
                    'is_latest' => 1
                ]);
            }

        });


        $this->getCyclesFromAds();
    }

    public function getCyclesFromAds()
    {
        Ad::all()->each(function ($ad) {
            $json_date = explode('{', $ad->campaign_name);
            if (count($json_date) > 1) {

                $json_date = $json_date[1];

                // dd($json_date);

                // dd('{'.$json_date);
                try {
                    $json_date = json_decode('{' . $json_date, true);

                    if (array_key_exists('cycle_no', $json_date) && array_key_exists('ver_no', $json_date)) {

                        $cycle = Cycle::updateOrCreate([
                            'cycle_no' => $json_date['cycle_no']
                        ], [
                        ]);

                        if($cycle){
                            $cycleVersion = CycleVersion::updateOrCreate([
                                'ver_no' => $json_date['ver_no'],
                                'cycle_id' => $cycle->id,
                            ], [
                                'cycle_id' => $cycle->id,
                                'campaign_ref_id' => $ad->campaing_ref_id,
                                'cycle_no' => $json_date['cycle_no'],
                                'campaign_name' => explode('{', $ad->campaign_name)[0],
                                'start_date' => $ad->campaing_start_date,
                                'end_date' => $ad->campaing_end_date,
                            ]);

                            $ad->update([
                                'cycle_version_id' => $cycleVersion->id
                            ]);


                        }



                    }
                } catch (\JsonException $exception) {
                    Log::debug($exception->getMessage());
                }
            } else {
            }
        });
    }


    public function getRunningStatus($ad){
        if($ad->ad_ref_status == 'ACTIVE'){
            if($ad->ad_set_ref_status == 'ACTIVE'){
                    return 'on';
            }else{
                if(Carbon::make($ad->end_date)->lessThan(Carbon::now())){
                    return 'completed';
                }
                return 'off';
            }
        }else{
            if(Carbon::make($ad->end_date)->lessThan(Carbon::now())){
                return 'completed';
            }
            return 'off';
        }
    }


    public function getGrowthData($ad){
        if($ad->completed_sales_profit >=  (2*$ad->amount_spent)){
            return 'upscale';

        }elseif($ad->completed_sales_profit >=  $ad->amount_spent){
            return 'steady';
        }else{
            return 'downscale';
        }
    }
}
