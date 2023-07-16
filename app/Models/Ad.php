<?php

namespace App\Models;

use App\actions\getAdPreformanceStatusAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\App;

class Ad extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'ad_ref_id',
        'ad_set_ref_id',
        'ad_ref_status',
        'ad_ref_effective_status',
        'ad_set_ref_status',
        'last_ad_update_at',
        'cost_per_message',
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
        'is_closed'
    ];


    public function scopeOfType($query, $type)
    {
        if($type == 'all'){
            return $query;
        }

        if($type == 'completed'){
            return $query
            ->where('ad_ref_effective_status','ADSET_PAUSED')
            ->where('end_date','>=',now()->toDateTimeString())
            ->whereDate('end_date','>=',now()->toDateString());

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



    // public function setProductNameAttribute($value)
    // {
    //     $this->attributes['product_name'] = $this->product->name;
    // }

    // public function setWarehouseNameAttribute(){
    //     $this->attributes['warehouse_name'] = implode(' / ',$this->warehouses->pluck('name')->toArray());
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

    public function warehouses()  {
        return $this->hasMany(AdWarehouse::class);
    }



}
