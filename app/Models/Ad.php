<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ad extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'ad_ref_id',
        'ad_ref_status',
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
        // dd($this->warehouses->pluck('warehouse')->pluck('name')->toArray());
        $status = (((double)$this->completed_sales_profit - (double)$this->amount_spent) < 0) && ((double)$this->amount_spent > 50);

        if($status){
            $status = 'loser';
        }

        if( $status !== 'loser' ){
            if((double)$this->completed_sales_profit - (double)$this->amount_spent < 80){
                // mehh
                $status = 'average';
            }else{
                // good
                $status = 'success';
            }
        }

        return $status;
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
