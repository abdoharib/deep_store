<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdWarehouse extends Model
{
    protected $table='ad_warehouse';
    use HasFactory;

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
