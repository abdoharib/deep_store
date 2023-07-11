<?php

namespace App\actions;

use Carbon\Carbon;

class getAdsAmountSpent
{
    public function invoke($date_start, $date_end)
    {


        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID', '193483383509873'),
            'app_secret' => env('FACEBOOK_APP_SECRET', 'a5819237862894e7c0871fb1953a2bff'),
            'default_access_token' => env('ACCESS_TOKEN','EAACvZBNxYE3EBACZA9iUEZAP2DnigTUoq5xpjhOsnJjh27F8N4CqdHOVK9iHbkeWRz8ysfkefeX63jBGOCad2BaNok3HS7xeCof4CIzZCiZAIm26qZAdSSF4PPCpMZAaKUWPsTnSfZAgZBrf6yvhaSnOl6QmOPB0P56fNCHJr0H7ZCQCSZA8C9DDTtN'),
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
