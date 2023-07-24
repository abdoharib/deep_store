<?php

namespace App\actions;

use App\Models\Ad;
use App\Models\Sale;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;

class DailyRiskMangement
{

    public $adsRiskMangement;
    public function __construct(adsRiskMangement $adsRiskMangement)
    {
        $this->adsRiskMangement = $adsRiskMangement;
    }

    public function invoke()
    {


        $NoSales = Sale::where('deleted_at', null)
            ->where('date', '=', SupportCarbon::now()->toDateString())
            ->count();
        Log::debug('No of Sales Today : ' . $NoSales);

        $amountSpent = Ad::ofType('active')
            ->where('date', '=', SupportCarbon::now()->toDateString())
            ->where('deleted_at', '=', null)
            ->get()
            ->sum('amount_spent');
        Log::debug('Amount Spent of All Active Ads Today : ' . $amountSpent);

        $cost_per_sale = ($amountSpent / $NoSales);
        $is_cost_per_sale_grater_then_10 =  $cost_per_sale > 10;

        if ($is_cost_per_sale_grater_then_10) {

            $no_ads_turned_off = 0;
            foreach (Ad::ofType('active')->where('deleted_at', '=', null)->get() as $activeAd) {
                if ($activeAd->preformance_status == 'average') {
                    $this->adsRiskMangement->turnOffAd($activeAd);
                    Log::debug("successfully turned off ad " . $activeAd->ad_ref_id);
                    $no_ads_turned_off++;
                }
            }
            Log::debug("Closed " . $no_ads_turned_off . ' average ads , daily cpr > 10');
        }
    }
}
