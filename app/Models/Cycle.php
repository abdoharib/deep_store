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
        'end_date',
        'products',
    ];


    public function getStartDateAttribute(){
        $val = $this->cycleVersions()->orderBy('ver_no','asc')->first();
        return $val ? $val->start_date : null;
    }
    public function getEndDateAttribute(){
        $val = $this->cycleVersions()->orderBy('ver_no','desc')->first();
        return $val ? $val->end_date : null;
    }

    public function getProductsAttribute(){
        $ads = Ad::query()
        ->whereIn('cycle_version_id',$this->cycleVersions->pluck('id')->toArray())
        ->get();

        // return $ads
        // ->groupBy('product_name');

        return $ads
        ->groupBy('product_name')
        ->map(function($ad,$key){
            // if($key == 'منظف فنارات فلامنقو'){
            //     dd($ad->sortBy('running_status'));
            // }

            $x = $ad->sortBy('running_status')->first();
            return [
                'name' => $x->product_name,
                'is_on' => ($x->running_status == 'on') ? true : false,
            ];
        });
    }



    public function cycleVersions(){
        return $this->hasMany(CycleVersion::class);
    }
}
