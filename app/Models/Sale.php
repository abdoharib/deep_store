<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'answer_status','vanex_shipment_status',
        'date', 'postponed_date', 'Ref', 'is_pos', 'client_id', 'GrandTotal', 'qte_retturn', 'TaxNet', 'tax_rate', 'notes',
        'total_retturn', 'warehouse_id', 'user_id', 'statut', 'discount', 'shipping','address','delivery_note',
        'paid_amount', 'payment_statut', 'created_at', 'updated_at', 'deleted_at','shipping_status',
        'vanex_sub_city_id','vanex_city_id','vanex_shipment_sticker_notes','vanex_shipment_code','shipping_provider','seen_at','cancel_reason','updated_by','last_vanex_update'
    ];

    protected $casts = [
        'is_pos' => 'integer',
        'GrandTotal' => 'double',
        'qte_retturn' => 'double',
        'total_retturn' => 'double',
        'user_id' => 'integer',
        'client_id' => 'integer',
        'warehouse_id' => 'integer',
        'discount' => 'double',
        'shipping' => 'double',
        'TaxNet' => 'double',
        'tax_rate' => 'double',
        'paid_amount' => 'double',
    ];

    private $append = [
        'due',
        'sale_cost',
        'has_stock'
    ];

    public function getDueAttribute() {
        return number_format( $this->GrandTotal - $this->paid_amount, 2, '.', '');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function details()
    {
        return $this->hasMany('App\Models\SaleDetail');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }

    public function facture()
    {
        return $this->hasMany('App\Models\PaymentSale');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function shipment()  {
        return $this->hasOne(Shipment::class);

    }


    public function getSaleCostAttribute(){

        $cost = 0;
        foreach ($this->details as $detail) {
            $cost =+ ($detail->product->cost * $detail->quantity);
        }
        return $cost;
    }


    public function getHasStockAttribute(){

        $has_stock = true;
        foreach ($this->details as $detail) {
            if(!$detail->has_stock){
                $has_stock = false;
                break;
            }
        }
        return $has_stock;
    }





}
