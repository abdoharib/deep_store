<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class SaleDetail extends Model
{

    use BelongsToTenant;

    protected $fillable = [
        'id', 'date', 'sale_id','sale_unit_id', 'quantity', 'product_id', 'total', 'product_variant_id',
        'price', 'TaxNet', 'discount', 'discount_method', 'tax_method','imei_number'
    ];

    protected $casts = [
        'id' => 'integer',
        'total' => 'double',
        'quantity' => 'double',
        'sale_id' => 'integer',
        'sale_unit_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'price' => 'double',
        'TaxNet' => 'double',
        'discount' => 'double',
    ];


    private $append = [
        'has_stock',
    ];

    public function sale()
    {
        return $this->belongsTo('App\Models\Sale');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }

    public function getHasStockAttribute(){

        $product_warehouse = product_warehouse::where('product_id', $this->product_id)
            ->where('deleted_at', '=', null)->where('warehouse_id', $this->sale->warehouse_id)
            ->where('product_variant_id', '=', null)->first();

        return $this->quantity <= $product_warehouse->qte ;
    }



}
