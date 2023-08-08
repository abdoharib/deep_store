<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class LeaveType extends Model
{
    use HasFactory;
    use BelongsToTenant;


    protected $dates = ['deleted_at'];

    protected $fillable = [
        'title'
    ];
}
