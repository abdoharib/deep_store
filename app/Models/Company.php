<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Company extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        "name",'email','phone','country'
    ];



}
