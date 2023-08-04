<?php

namespace App\Console\Commands;

use App\actions\adsRiskMangement;
use App\actions\updateAdsAction;
use App\Models\Ad;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:ads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */

     private $updateAdsAction=null;
     private $adsRiskMangement=null;

    public function __construct(updateAdsAction $updateAdsAction, adsRiskMangement $adsRiskMangement)
    {
        parent::__construct();

        $this->updateAdsAction = $updateAdsAction;
        $this->adsRiskMangement = $adsRiskMangement;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Log::debug("first");

            // $this->updateAdsAction->invoke();
            // $this->adsRiskMangement->invoke();
            Ad::query()->update([
                'is_latest' => null
            ]);
            Product::all()->each(function($product){
                $ad = $product->ads()->orderBy('start_date','desc')->first();

                if($ad){

                    $another_running_ad_q = Ad::query()
                    ->where('product_id',$ad->product_id)
                    ->whereHas('warehouses',function($q) use ($ad){
                        $q->whereIn('warehouse_id',$ad->warehouses->pluck('id')->toArray());
                    })
                    ->where('running_status','on')
                    ->orderBy('start_date','desc');
                    if($product->id == 13){

                        Log::debug($another_running_ad_q->first());
                    }

                    if($another_running_ad_q->first()){
                        $another_running_ad_q->update([
                            'is_latest' => 1
                        ]);
                    }else{
                        $ad->update([
                            'is_latest' => 1
                        ]);
                    }



                }

            });
            Log::debug("successfully updated");
        }catch(\Exception $e){
            Log::debug($e->getMessage());
        }
    }
}
