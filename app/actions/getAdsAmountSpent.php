<?php

namespace App\actions;

use App\Models\Setting;
use Carbon\Carbon;

class getAdsAmountSpent
{
    public function invoke($date_start, $date_end)
    {
        dd($date_start);


        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => Setting::first()->facebook_app_id,
            'app_secret' => Setting::first()->facebook_app_secret,
            'default_access_token' => Setting::first()->facebook_user_token,
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->get('/act_724531662792327/insights?time_range={"since":"'.$date_start.'","until":"'.$date_end.'"}');
            $date = $response->getDecodedBody()['data'];
            if(count($date)){
                return (float) $date[0]['spend'] *5;
            }else{
                return 0;
            }
            // return (float)$date['spend'] *5;

        } catch (\Exception $e) {
            dd($e->getMessage());

        }


    }
}
