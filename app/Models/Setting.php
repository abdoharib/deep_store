<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Setting extends Model
{
    use BelongsToTenant;


    protected $fillable = [
        'currency_id', 'email', 'CompanyName', 'CompanyPhone', 'CompanyAdress',
         'logo','footer','developed_by','client_id','warehouse_id','default_language',
         'is_invoice_footer','invoice_footer','vanex_api_key',
         'facebook_user_token','facebook_app_id','facebook_app_secret'
    ];

    protected $casts = [
        'currency_id' => 'integer',
        'client_id' => 'integer',
        'is_invoice_footer' => 'integer',
        'warehouse_id' => 'integer',
    ];

    public function Currency()
    {
        return $this->belongsTo('App\Models\Currency');
    }

}
