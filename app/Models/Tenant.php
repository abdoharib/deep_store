<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Tenant extends BaseTenant
{
    // use HasDatabase;



    public function users(){
        return $this->hasMany(User::class);
    }

    public function roles(){
        return $this->hasMany(Role::class);
    }



}
