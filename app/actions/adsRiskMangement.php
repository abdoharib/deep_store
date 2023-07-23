<?php

namespace App\actions;

use App\Models\Ad;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;

class adsRiskMangement
{
    public function invoke(){

        $ads = Ad::all();
        $ads_manged_risk = 0;

        foreach ($ads as $ad) {

            if($ad->preformance_status == 'loser'){
                Log::debug('ad '.$ad->ad_ref_id . ' is a loser');
                if($ad->ad_ref_status == 'ACTIVE'){
                    Log::debug('ad '.$ad->ad_ref_id . ' is Active');
                    $this->turnOffAd($ad);
                }
            };
            $ads_manged_risk++;
        }

        $LastEndingActiveAds = Ad::ofType('active')->where('deleted_at', '=', null)->sort('DESC','end_date')->first();
        $FirstStartingActiveAds = Ad::ofType('active')->where('deleted_at', '=', null)->sort('ASC','start_date')->first();

        $NoSales = Sale::where('deleted_at',null)
        ->where('date','>=',$FirstStartingActiveAds->start_date)
        ->where('date','<=',$LastEndingActiveAds->end_date)
        ->count();


        Log::debug('No of Sales Between'.$FirstStartingActiveAds->start_date.' and '.$LastEndingActiveAds->end_date);
        $amountSpent = Ad::ofType('active')->where('deleted_at', '=', null)->get()->sum('amount_spent');
        Log::debug('Amount Spent of All Active Ads'.$amountSpent);


        if((float)$amountSpent > 150){
            if($NoSales < 15 ){

                foreach ( Ad::ofType('active')->where('deleted_at', '=', null)->get() as $activeAd ){
                    if($activeAd->preformance_status == 'average'){
                        $this->turnOffAd($activeAd);

                    }
                }

            }
        }



        Log::debug('looped over and checked '.$ads_manged_risk . ' for spend > profit');
    }

    public function turnOffAd(Ad $ad){

        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID','193483383509873'),
            'app_secret' => env('FACEBOOK_APP_SECRET','a5819237862894e7c0871fb1953a2bff'),
            'default_access_token' => env('ACCESS_TOKEN'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->post('/'.$ad->ad_ref_id,[
                'status' => 'PAUSED',
            ]);
            Log::debug("successfully turned off ad ".$ad->ad_ref_id);
            $ad->update([
                'ad_ref_status' => 'PAUSED',
                'closed_at' => SupportCarbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::debug($e);
        }
    }
}
