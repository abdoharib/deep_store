<?php
namespace App\utils;

use App\Models\Currency;
use App\Models\Role;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Facade\FlareClient\Flare;
use Illuminate\Support\Carbon as SupportCarbon;

class helpers
{

    //  Helper Multiple Filter
    public function filter($model, $columns, $param, $request)
    {
        // Loop through the fields checking if they've been input, if they have add
        //  them to the query.
        $fields = [];
        for ($key = 0; $key < count($columns); $key++) {
            $fields[$key]['param'] = $param[$key];
            $fields[$key]['value'] = $columns[$key];
        }

        foreach ($fields as $field) {
            $model->where(function ($query) use ($request, $field, $model) {
                return $model->when($request->filled($field['value']),
                    function ($query) use ($request, $model, $field) {
                        $field['param'] = 'like' ?
                        $model->where($field['value'], 'like', "{$request[$field['value']]}")
                        : $model->where($field['value'], $request[$field['value']]);
                    });
            });
        }

        // Finally return the model
        return $model;
    }

    public function getTimeframePeriods($start, $end, $timeframe)
    {
        if(now()->lessThan($end)){
            $end = now();
        }

        if($timeframe == 'daily'){
            // return CarbonPeriod::create($start->toDateString(),$end->toDateString());
            // dd($start);
            return collect($start->range($end->toDateString(), CarbonInterval::day(),)
            ->toArray())->map(function($v){
                if(now()->lessThan($v)){
                    return false;
                }


                return [
                'start' => $v->startOfDay(),
                'end' =>
                (
                    $v->endOfDay()->subMinute()->lessThan(now())
                    ?
                    $v->endOfDay()->subMinute()
                    :
                    now()

                ),
                $v->endOfDay()->subMinute(),
                'period_name' => $v->format('M d')
            ];
        })->filter(function($v){ return $v; });
        }




        if($timeframe == 'weekly'){
            return collect($start->range($end->toDateString(), CarbonInterval::week(),)
            ->toArray())->map(function($v){

                if(now()->lessThan($v)){
                    return false;
                }

                return [
                'start' => $v->startOfWeek()->toDateString(),
                'end' => (
                    $v->endOfWeek()->lessThan(now())
                    ?
                    $v->endOfWeek()->toDateString()
                    :
                    now()->toDateString()

                ),
                'period_name' => $v->format('M').' Week '.$v->weekOfMonth
            ];
        })->filter(function($v){ return $v; })->map(function($v){
            return [
                'start' => Carbon::make($v['start']),
                'end' => Carbon::make($v['end']),
                'period_name' => $v['period_name']
            ];
        });
        }

        if($timeframe == 'monthly'){
            return collect($start->range($end->toDateString(), CarbonInterval::month(),)
            ->toArray())->map(function($v){

                if(now()->lessThan($v)){
                    return false;
                }

                return [
                'start' => $v->startOfMonth()->toDateString(),
                'end' => (
                    $v->startOfMonth()->lessThan(now())
                    ?
                    $v->endOfMonth()->toDateString()
                    :
                    now()->toDateString()

                ),
                'period_name' => $v->format('M')
            ];
        })->filter(function($v){ return $v; })->map(function($v){
            return [
                'start' => Carbon::make($v['start']),
                'end' => Carbon::make($v['end']),
                'period_name' => $v['period_name']
            ];
        });
        }


    }

    //  Check If Hass Permission Show All records
    public function Show_Records($model)
    {
        $Role = Auth::user()->roles()->first();
        $ShowRecord = Role::findOrFail($Role->id)->inRole('record_view');

        if (!$ShowRecord) {
            return $model->where('user_id', '=', Auth::user()->id);
        }
        return $model;
    }

    // Get Currency
    public function Get_Currency()
    {
        $settings = Setting::with('Currency')->where('deleted_at', '=', null)->first();

        if ($settings && $settings->currency_id) {
            if (Currency::where('id', $settings->currency_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $symbol = $settings['Currency']->symbol;
            } else {
                $symbol = '';
            }
        } else {
            $symbol = '';
        }
        return $symbol;
    }

    // Get Currency COde
    public function Get_Currency_Code()
    {
        $settings = Setting::with('Currency')->where('deleted_at', '=', null)->first();

        if ($settings && $settings->currency_id) {
            if (Currency::where('id', $settings->currency_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $code = $settings['Currency']->code;
            } else {
                $code = 'usd';
            }
        } else {
            $code = 'usd';
        }
        return $code;
    }

}
