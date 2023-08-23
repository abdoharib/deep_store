<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Currency extends Model
{
    // use BelongsToTenant;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'code', 'name', 'symbol',
    ];

}
