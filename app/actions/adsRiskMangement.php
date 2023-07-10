<?php

namespace App\actions;

use App\Models\Ad;
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
                    $this->turnOffAd($ad->ad_ref_id);
                }
            };
            $ads_manged_risk++;
        }

        Log::debug('looped over and checked '.$ads_manged_risk . ' for spend > profit');
    }

    public function turnOffAd(Ad $ad){

        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID','193483383509873'),
            'app_secret' => env('FACEBOOK_APP_SECRET','a5819237862894e7c0871fb1953a2bff'),
            'default_access_token' => env('ACCESS_TOKEN','EAACvZBNxYE3EBAKprFnsWTfViLWBmtDkLq3Jo3IWOOglNFd0ymudZBZAC3Dif08EljHLPLulm3J5vPGEaCaZCXf1BZAzmcuuZBB43C32x8UnSxWZBoh7heZAgcOeKSZCDQRIJZBSJ8Pwam1fFpIIYQnJwrqC87gq39ZAyDoRkXbmS6ZAUMAdoHJFdxn2'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->post('/'.$ad->ad_ref_id,[
                'status' => 'PAUSED',
            ]);
            Log::debug("successfully turned off ad ".$ad->ad_ref_id);
            $ad->update([
                'closed_at' => SupportCarbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::debug($e);
        }
    }
}
