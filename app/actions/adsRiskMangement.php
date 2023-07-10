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
            'default_access_token' => env('ACCESS_TOKEN','EAACvZBNxYE3EBAFNCfR84RU4a0HGmHsmU4kPjI0oYKc0XIx0n7xWBpfqsRP0Uw1wPCdPViY3dx0XZARQGcdQwNWrfl1OoYYMpW3DTPh6bkrpCVlPA3HhrR9L3B9ZBmBAuyZCeJ8Nm6TcBlxqyXFgr78Rirj9FvTzJfH2QD0rqaeoa4reEXRbMZBRcwgaMLJ7WHf3ZBGbrTrheuogAnakjA'),
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
