<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class sms_gateway extends Model
{
    use BelongsToTenant;

    protected $table = 'sms_gateway';

    protected $fillable = [
        'title',
    ];


}
