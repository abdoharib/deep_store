<?php

namespace App\actions;

use App\Models\Ad;
use Carbon\Carbon;

class getAdPreformanceStatusAction
{
    public function invoke(Ad $ad)
    {

        $ad_net_profit = (double)$ad->completed_sales_profit - (double)$ad->amount_spent;

        $ad_is_spent_over_50_and_profit_less_then_ad_cost = ( $ad_net_profit < 0) && ((double)$ad->amount_spent > 50);

        $ad_cpr_is_grater_then_3_and_spent_more_then_30 = ( ((double)$ad->cost_per_result > 3.5) && ($ad->amount_spent > 30) );
        return $ad->cost_per_result;

        if($ad_is_spent_over_50_and_profit_less_then_ad_cost){
            return 'loser';
        }

        if($ad_cpr_is_grater_then_3_and_spent_more_then_30){
            return 'loser';
        }

        if( $ad_net_profit < 80 ){
            return 'average';
        }


        return 'success';

        // if( $status !== 'loser' ){
        //     if((double)$this->completed_sales_profit - (double)$this->amount_spent < 80){
        //         // mehh
        //         $status = 'average';
        //     }else{
        //         // good
        //         $status = 'success';
        //     }
        // }



    }
}
