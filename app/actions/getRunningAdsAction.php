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
     

        $response = $facebook->get('/act_724531662792327/ads?limit=100&fields=campaign{name,lifetime_budget,budget_remaining},name,status,created_time,adset{name,budget_remaining,lifetime_budget,daily_budget,end_time}');
        $ads = $response->getDecodedBody()['data'];

        $ads = array_filter($ads,function($ad){
            if( $ad['status']=='ACTIVE' ){
                return true;
            }
            return false;
        });



        $ads = array_map(function ($item) use($facebook) {

            $id = explode('product_id:',$item['name']);
            $warehouse_id = explode('product_id:',$item['name']);
            if(count($id)>1){
                $id = $id[1];
            }else{
                $id=null;
            }
            if(count($warehouse_id)>1){
                $warehouse_id = $warehouse_id[1];
            }else{
                $warehouse_id=null;
            }


            $ad_insight = $facebook->get('/'.$item['id'].'/insights?fields=ad_id,spend&time_range={"since":"2023-03-01","until":"'.Carbon::now()->format('Y-m-d').'"}');
            $data = $ad_insight->getDecodedBody()['data'];

            $spent = 0;
            if(count($data)){
               $spent =  $data[0]['spend'];
            }
            

            return array_merge($item,[
                'product_id' =>$id,
                'warehouse_id' => $warehouse_id
                'total_spent' => (float)$spent
            ]);
        },$ads);


        return $ads;
    }
}
