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
            'default_access_token' => env('ACCESS_TOKEN', 'EAACvZBNxYE3EBAKprFnsWTfViLWBmtDkLq3Jo3IWOOglNFd0ymudZBZAC3Dif08EljHLPLulm3J5vPGEaCaZCXf1BZAzmcuuZBB43C32x8UnSxWZBoh7heZAgcOeKSZCDQRIJZBSJ8Pwam1fFpIIYQnJwrqC87gq39ZAyDoRkXbmS6ZAUMAdoHJFdxn2'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->get('/act_724531662792327/insights?time_range={"since":"'.$date_start.'","until":"'.$date_end.'"}');
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
        $date = $response->getDecodedBody()['data'][0];


        return (float)$date['spend'];
    }
}
