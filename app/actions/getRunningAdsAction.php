<?php

namespace App\actions;
use Carbon\Carbon;

class getRunningAdsAction
{
    public function invoke(){


        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' => env('FACEBOOK_APP_ID','193483383509873'),
            'app_secret' => env('FACEBOOK_APP_SECRET','a5819237862894e7c0871fb1953a2bff'),
            'default_access_token' => env('ACCESS_TOKEN'),
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

try {
    $response = $facebook->get('/act_724531662792327/ads?limit=100&fields=campaign{name,lifetime_budget,budget_remaining},name,effective_status,status,effective_status,created_time,adset{name,budget_remaining,lifetime_budget,daily_budget,end_time,status,start_time}');
} catch (\Exception $e) {
    dd($e->getMessage());
}
        $ads = $response->getDecodedBody()['data'];

        // $ads = array_filter($ads,function($ad){
        //     if( $ad['status']=='ACTIVE' ){
        //         return true;
        //     }
        //     return false;
        // });



        $ads = array_map(function ($item) use($facebook) {

            $json_date = explode('{',$item['name']);
            if(count($json_date)>1){
                $json_date = $json_date[1];

                // dd('{'.$json_date);
                try{
                    $json_date = json_decode('{'.$json_date,true);

                } catch (\JsonException $exception) {
                }

            }else{
                return false;
                $json_date = [
                    'product_id' => null,
                    'warehouse_id' => null
                ];
            }


            $ad_insight = $facebook->get('/'.$item['id'].'/insights?fields=ad_id,spend&time_range={"since":"2023-03-01","until":"'.Carbon::now()->format('Y-m-d').'"}');
            $data = $ad_insight->getDecodedBody()['data'];

            $spent = 0;
            if(count($data)){
               $spent =  $data[0]['spend'];
            }

            if(is_null($json_date)){
                //throw new \Exception($item['name']);
                return null;

            }



            return array_merge($item,[
                'product_id' => $json_date['product_id'],
                'warehouse_id' => $json_date['warehouse_id'],
                'total_spent' => (float)$spent
            ]);
        },$ads);

        $ads = array_filter($ads,function($item){
            return $item;
        });


        return $ads;
    }
}
