<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EmployeeExperience extends Model
{
    use HasFactory;
    use BelongsToTenant;


    protected $dates = ['deleted_at'];

    protected $fillable = [
        'employee_id','title','company_name','location','employment_type','start_date',
        'end_date','description'

    ];

    protected $casts = [
        'employee_id'     => 'integer',
    ];


    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

}
