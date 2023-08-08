<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Attendance extends Model
{
    use BelongsToTenant;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id','employee_id','company_id','date','clock_in',
        'clock_in_ip','clock_out_ip',
        'clock_in_out','depart_early','late_time','clock_out',
        'overtime','total_work','total_rest','status'

    ];

    protected $casts = [
        'user_id'    => 'integer',
        'employee_id'  => 'integer',
        'company_id'  => 'integer',
        'clock_in_out'  => 'integer',
    ];



    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }


}
