<?php

namespace App\actions;

use App\Models\Ad;
use App\Models\Sale;
use App\Models\Setting;
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

        $LastEndingActiveAds = Ad::ofType('active')->where('deleted_at', '=', null)->orderBy('end_date','DESC')->first();
        $FirstStartingActiveAds = Ad::ofType('active')->where('deleted_at', '=', null)->orderBy('start_date','ASC')->first();




        Log::debug('looped over and checked '.$ads_manged_risk . ' for spend > profit');
    }

    public function turnOffAd(Ad $ad){

        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => Setting::first()->facebook_app_id,
            'app_secret' => Setting::first()->facebook_app_secret,
            'default_access_token' => Setting::first()->facebook_user_token,
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
