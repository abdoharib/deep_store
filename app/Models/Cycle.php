<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Cycle extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'campaign_ref_id',
        'cycle_no',
        'name',
        'start_date',
        'end_date',
    ];

    protected $appends = [
        'days_left',
        'no_days',
        'days_since_start',
        'no_ads',
        'no_successful_ads',
        'no_unsuccessful_ads',
        'total_lost',
        'win_rate',
        'maturity_rate',
        'total_budget'
    ];


    public function scopeByStatus($query, $status){
        if($status == 'ACTIVE'){
            $query->whereHas('ads', function($q){
                $q->where('ad_ref_status','ACTIVE')
                ->where('ad_set_ref_status','ACTIVE')
                ->where('end_date','>=',Carbon::now()->toDateTimeString())
                ;
            });
        }

        if($status == 'INACTIVE'){
            $query->whereHas('ads', function($q){
                $q->where('ad_ref_status','PAUSED')
                ->orWhere('ad_set_ref_status','PAUSED')
                ->orWhere('end_date','<',Carbon::now()->toDateTimeString())
                ;
            });
        }
    }
    public function getDaysLeftAttribute(){
        return Carbon::make($this->end_date)->diffInDays(Carbon::now());
    }
    public function getDaysSinceStartAttribute(){
        return Carbon::now()->diffInDays(Carbon::make($this->start_Date));
    }
    public function getNoDaysAttribute(){
        return Carbon::make($this->end_date)->diffInDays(Carbon::make($this->start_date));
    }
    public function getNoAdsAttribute(){
        return $this->ads->count();
    }
    public function getNoSuccessfulAdsAttribute(){
        return $this->ads->filter(function($ad){
            $ad->preformance_status == 'success';
        });
    }
    public function getNoUnsuccessfulAdsAttribute(){
        return $this->ads->filter(function($ad){
            $ad->preformance_status != 'success';
        });
    }
    public function getTotalLostAttribute(){
        return $this->filter(function($ad){
            $ad->preformance_status != 'success';
        })->sum('amount_spent');
    }
    public function getWinRateAttribute(){

        $no_winning_ads = $this->ads->filter(function($ad){
            $ad->preformance_status == 'success';
        })->count();
        $no_ads = $this->ads()->count();

        if($no_ads){
            return ($no_winning_ads / $no_ads);
        }
        return 0;
    }

    public function getMaturityRateAttribute(){

        $no_sales = $this->ads->sum('no_sales');
        $no_completed_sales = $this->ads->sum('no_completed_sales');

        if($no_sales){
            return ($no_completed_sales / $no_sales);
        }
        return 0;
    }

    public function getTotalBudgetAttribute(){

        return $this->ads->sum('lifetime_budget');
    }

    public function ads(){
        return $this->hasMany(Ad::class);
    }
}
