<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class OauthRefreshToken extends Model
{
    use BelongsToTenant;


    public function oauthAccessToken()
    {
        return $this->belongsTo('\App\Models\OauthAccessToken');
    }

}
