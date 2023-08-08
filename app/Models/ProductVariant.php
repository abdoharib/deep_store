<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ProductVariant extends Model
{
    use BelongsToTenant;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id', 'name', 'qty',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'qty' => 'double',
    ];

}
