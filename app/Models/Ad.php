<?php

namespace App\Models;

use App\actions\getAdPreformanceStatusAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;

class Ad extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'cycle_version_id',


        'campaign_name',
        'campaing_ref_id',

        'campaing_start_date',
        'campaing_end_date',


        'ad_ref_id',
        'ad_set_ref_id',
        'ad_ref_status',
        'ad_ref_effective_status',
        'ad_set_ref_status',

        'last_ad_update_at',
        'cost_per_message',
        'lifetime_budget',
        'product_id',
        'closed_at',
        'start_date',
        'end_date',

        'amount_spent',
        'completed_sales_profit',

        'no_sales',
        'revenue_sales',

        'no_completed_sales',
        'revenue_completed_sales'

    ];

    protected $casts = [
        'amount_spent' => 'double',
    ];

    protected $appends = [
        'preformance_status',
        'product_name',
        'warehouse_name',
        'is_closed',
        'growth_data',
        'no_muture_sales',
        'running_status',
    ];


    public function scopeOfType($query, $type)
    {
        if($type == 'all'){
            return $query;
        }

        if($type == 'completed'){
            return $query
            ->where('ad_ref_effective_status','ADSET_PAUSED')
            ->orWhere('end_date','<=',now()->toDateTimeString());

        }

        if($type == 'closed'){
            return $query
            ->where('closed_at','!=',null);
        }

        if($type == 'active'){
            return $query
            ->where('ad_ref_status','ACTIVE')
            ->where('ad_set_ref_status','ACTIVE')
            ->where('end_date','>=',now()->toDateString());
        }
        // if($type == 'no_stock'){
        //     return $query
        //     ->where('ad_ref_status','ACTIVE')
        //     ->where('ad_set_ref_status','ACTIVE')
        //     ->whereHas('product',function($q){
        //         $q->whereHas('product_warehouse',function($q){

        //         });
        //     });
        // }
    }

    public function getNoMutureSalesAttribute(){

    }

    // public function scopeOfPreformanceStatus($query, $status)
    // {
    //     if($status == 'success'){
    //         return $query;
    //     }

    //     if($status == 'loser'){
    //         return $query
    //         ->where('ad_ref_effective_status','ADSET_PAUSED')
    //         ->orWhere('end_date','<=',now()->toDateTimeString());

    //     }

    //     if($status == 'average'){
    //         return $query
    //         ->where('closed_at','!=',null);
    //     }
    // }


    public function getWarehouseNameAttribute($value){
        // dd($this->warehouses->pluck('warehouse')->pluck('name')->toArray());
        return implode(' / ',$this->warehouses->pluck('warehouse')->pluck('name')->toArray());
    }

    public function getIsClosedAttribute($value){
        // dd($this->warehouses->pluck('warehouse')->pluck('name')->toArray());
        return $this->closed_at ? true: false;
    }

    public function getPreformanceStatusAttribute($value){
        return App::make(getAdPreformanceStatusAction::class)->invoke($this);
    }

    public function getRunningStatusAttribute($value){
        if($this->ad_ref_status == 'ACTIVE'){
            if($this->ad_set_ref_status == 'ACTIVE'){
                if(Carbon::make($this->end_date)->lessThan(Carbon::now())){
                    return 'completed';
                }else{
                    return 'on';
                }
            }else{
                return 'off';
            }
        }else{
            return 'off';
        }
    }

    public function getProductNameAttribute($value){
        if($this->product){
            return $this->product->name;
        }else{
            return '';
        }
    }


    // public function getNoSalesAttribute() {
    //     if($this->product){
    //         $warehouses =  $this->warehouses->pluck('warehouse')->pluck('id')->toArray();
    //         // dd($warehouses);

    //         $data = SaleDetail::where('product_id',$this->product->id)
    //         ->whereHas('sale',function($q)use($warehouses){
    //             $q->whereIn('warehouse_id',$warehouses);
    //         })->get()->sum('quantity');
    //         return $data;
    //     }else{
    //         return '/';
    //     }

    // }

    // public function getNoCompletedSalesAttribute() {
    //     if($this->product){
    //         $warehouses =  $this->warehouses->pluck('warehouse')->pluck('id')->toArray();
    //         // dd($warehouses);
    //         $data = SaleDetail::where('product_id',$this->product->id)
    //         ->whereHas('sale',function($q)use($warehouses){
    //             $q->where('statut','completed')
    //             ->whereIn('warehouse_id',$warehouses);
    //         })->get()->sum('quantity');
    //         return $data;
    //     }else{
    //         return '/';
    //     }

    // }

    public function product() {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function cycleVersion() {
        return $this->belongsTo(CycleVersion::class);
    }

    public function warehouses()  {
        return $this->hasMany(AdWarehouse::class);
    }



    public function getGrowthDataAttribute($value){
        if($this->completed_sales_profit >=  (2*$this->amount_spent)){
            return [
                'status' => 'sucessful',
                'next_milestone_budget'=>(2*($this->lifetime_budget?$this->lifetime_budget:$this->amount_spent))
            ];
        }elseif($this->completed_sales_profit >=  $this->amount_spent){
            return [
                'status' => 'steady',
                'next_milestone_budget'=>(($this->lifetime_budget?$this->lifetime_budget:$this->amount_spent))
             ];
        }else{
            return [
                'status' => 'failed',
                'next_milestone_budget'=> 0
            ];
        }
    }

}
