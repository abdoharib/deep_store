<?php

namespace App\actions;

use App\Models\Ad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class adsRiskMangement
{
    public function invoke(){

        $ads = Ad::where('deleted_at',null)->get();

        foreach ($ads as $ad) {
            if($ad->preformance_status == 'loser'){
                if($ad->ad_ref_status == 'ACTIVE'){
                    $this->turnOffAd($ad->ad_ref_id);
                }
            };
        }
    }

    public function turnOffAd(string $ad_ref_id){

        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID','193483383509873'),
            'app_secret' => env('FACEBOOK_APP_SECRET','a5819237862894e7c0871fb1953a2bff'),
            'default_access_token' => env('ACCESS_TOKEN','EAACvZBNxYE3EBALAZAuhnFk712ic7V6cNWLYzbAJZCBP00AET1YZBdxp7JnNizXML1m3RbR4oZAsFXzZAiSZAGwENxHaulDUh8QJo3zGvD4ZCw0RIvwuZBJEI17XBbo4FEIGuDiwwSYWvbudfrVM84V30gy4PQUYtxM2RiHo7pDzcenJbpFPyBJBH'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->post('/'.$ad_ref_id,[
                'status' => 'PAUSED',
            ]);
        } catch (\Exception $e) {
            Log::debug($e);
        }
    }
}
