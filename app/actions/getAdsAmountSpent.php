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
            'default_access_token' => env('ACCESS_TOKEN', 'EAACvZBNxYE3EBAFNCfR84RU4a0HGmHsmU4kPjI0oYKc0XIx0n7xWBpfqsRP0Uw1wPCdPViY3dx0XZARQGcdQwNWrfl1OoYYMpW3DTPh6bkrpCVlPA3HhrR9L3B9ZBmBAuyZCeJ8Nm6TcBlxqyXFgr78Rirj9FvTzJfH2QD0rqaeoa4reEXRbMZBRcwgaMLJ7WHf3ZBGbrTrheuogAnakjA'),
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
