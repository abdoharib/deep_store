<?php

namespace App\actions;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class getRunningAdsAction
{
    public function invoke()
    {


        $facebook = new \JoelButcher\Facebook\Facebook([
            'app_id' =>Setting::first()->facebook_app_id,
            'app_secret' => Setting::first()->facebook_app_secret,
            'default_access_token' => Setting::first()->facebook_user_token,
            'default_graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v16.0'),
        ]);

        try {
            $response = $facebook->get('/act_724531662792327/ads?limit=500&fields=campaign{id,start_time,stop_time,name,lifetime_budget,budget_remaining},name,effective_status,status,created_time,adset{id,name,budget_remaining,lifetime_budget,daily_budget,end_time,status,start_time}');
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



        $ads = array_map(function ($item) use ($facebook) {


            try {


                $json_date = explode('{', $item['name']);
                if (count($json_date) > 1) {
                    $json_date = $json_date[1];

                    // dd('{'.$json_date);
                    try {
                        $json_date = json_decode('{' . $json_date, true);
                    } catch (\JsonException $exception) {
                    }
                } else {
                    return false;
                    $json_date = [
                        'product_id' => null,
                        'warehouse_id' => null
                    ];
                }


                $ad_insight = $facebook->get('/' . $item['id'] . '/insights?fields=ad_id,cost_per_action_type,spend&time_range={"since":"2023-03-01","until":"' . Carbon::now()->format('Y-m-d') . '"}');
                $data = $ad_insight->getDecodedBody()['data'];
                $cpr = 0;
                $spent = 0;
                if (count($data)) {
                    $spent =  $data[0]['spend'];

                    $v = array_filter($data[0]['cost_per_action_type'], function ($v) {
                        if ($v['action_type'] == 'onsite_conversion.other') {
                            return true;
                        } else {
                            return false;
                        }
                    });

                    // Log::debug(json_encode($v));
                    if (count($v)) {
                        $cpr = $v[array_key_first($v)]['value'];
                    }
                }

                if (is_null($json_date)) {
                    // throw new \Exception($item['name']);
                    return null;
                    Log::debug('error at ' . $item['name'] . ' ' . $item['id']);
                }


                // Log::debug($cpr);



                //convert int to array for backwards compatibility
                if(!is_array($json_date['product_id'])){
                    $json_date['product_id'] = [$json_date['product_id']];
                }

                return array_merge($item, [
                    'product_id' => $json_date['product_id'],
                    'warehouse_id' => $json_date['warehouse_id'],
                    'total_spent' => (float)$spent,
                    'cost_per_message' => $cpr
                ]);
            } catch (\Exception $e) {
                Log::debug($e->getMessage());
                return null;
            }
        }, $ads);

        $ads = array_filter($ads, function ($item) {
            return $item;
        });


        return $ads;
    }
}
