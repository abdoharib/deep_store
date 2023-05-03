<?php

namespace App\actions;

class getRunningAdsAction
{
    public function invoke(){
        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_access_token' => env('ACCESS_TOKEN'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        $response = $facebook->get('/act_724531662792327/campaigns?fields=name,lifetime_budget,budget_remaining,start_time');
        $ads = $response->getDecodedBody()['data'];
        $ads = array_map(function ($item){

            $id = explode('product_id:',$item['name']);
            if(count($id)>1){
                $id = $id[1];
            }else{
                $id=null;
            }
            return array_merge($item,[
                'product_id' =>$id
            ]);
        },$ads);

        return $ads;
    }
}
