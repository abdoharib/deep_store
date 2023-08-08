<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class PasswordReset extends Model
{

    use BelongsToTenant;

    protected $fillable = [
        'email', 'token','created_at','updated_at'
    ];

}
