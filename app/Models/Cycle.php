<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Cycle extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];


    protected $fillable = [
        'cycle_no',
    ];

    protected $appends = [
        'start_date',
        'end_date'
    ];


    public function getStartDateAttribute(){
        $val = $this->cycleVersions()->orderBy('ver_no','asc')->first();
        return $val ? $val->start_date : null;
    }
    public function getEndDateAttribute(){
        $val = $this->cycleVersions()->orderBy('ver_no','desc')->first();
        return $val ? $val->end_date : null;
    }



    public function cycleVersions(){
        return $this->hasMany(CycleVersion::class);
    }
}
