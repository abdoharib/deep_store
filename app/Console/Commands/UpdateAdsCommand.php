<?php

namespace App\Console\Commands;

use App\actions\adsRiskMangement;
use App\actions\getAdPreformanceStatusAction;
use App\actions\updateAdsAction;
use App\Models\Ad;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
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

        Ad::all()->each(function($ad){
            $ad->update([
                'preformance_status' => App::make(getAdPreformanceStatusAction::class)->invoke($ad),
                'running_status' => $this->getRunningStatus($ad),
                'growth_status' => $this->getGrowthData($ad)
            ]);

        });

        Product::all()->each(function($product){
            $ad = $product->ads()->orderBy('start_date','desc')->first();
            if($ad){
                $ad->update([
                    'is_latest' => 1
                ]);
            }
        });

        // try {
        //     // Log::debug("first");

        //     $this->updateAdsAction->invoke();
        //     $this->adsRiskMangement->invoke();
        //     Log::debug("successfully updated");
        // }catch(\Exception $e){
        //     Log::debug($e->getMessage());
        // }
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
