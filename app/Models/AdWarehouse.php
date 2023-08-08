<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AdWarehouse extends Model
{
    protected $table='ad_warehouse';
    use BelongsToTenant;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'ad_id',
        'warehouse_id',
    ];


    public function ad(){
        return $this->belongsTo(Ad::class);
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

}
