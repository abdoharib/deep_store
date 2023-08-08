<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class UserWarehouse extends Model
{
    use BelongsToTenant;

    protected $table ="user_warehouse";

   protected $fillable = [
    'user_id', 'warehouse_id',
];

protected $casts = [
    'user_id' => 'integer',
    'warehouse_id' => 'integer',
];

    public function assignedWarehouses()
    {
        return $this->hasMany('App\Models\Warehouse', 'id', 'warehouse_id');
    }
}
