<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Holiday extends Model
{
    use HasFactory;
    use BelongsToTenant;


    protected $dates = ['deleted_at'];

    protected $fillable = [
        'title','company_id','start_date','end_date','description'
    ];

    protected $casts = [
        'company_id'  => 'integer',
    ];

    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }
}
