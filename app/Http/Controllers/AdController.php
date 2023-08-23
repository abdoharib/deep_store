<?php

namespace App\Http\Controllers;

use App\actions\updateAdsAction;
use App\Models\Ad;
use App\Models\Product;
use App\Models\ProductAd;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\utils\helpers;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request as FacadesRequest;
use LaravelLegends\EloquentFilter\Rules\Has;

class AdController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(request $request, updateAdsAction $updateAdsAction)
    {
        try {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            // How many items do you want to display.
            $perPage = $request->limit;

            $pageStart = FacadesRequest::get('page', 1);
            // Start displaying items from this number;
            $offSet = ($pageStart * $perPage) - $perPage;
            $order = ($request->has('SortField') && $request->SortField ) ? $request->SortField :'end_date' ;
            $dir = 'desc';
            $helpers = new helpers();
            // Filter fields With Params to retrieve
            $param = array(
                0 => '=',
                1 => '=',
                2 => '=',

            );

            $columns = array(
                0 => 'start_date',
                1 => 'end_date',
                2 => 'ad_ref_status',
            );
            $data = array();

            $ads = Ad::query()
            ->when( ( $request->filled('filter_ad') && ($request->filter_ad != 'all') ),function($q) use($request){
                $q->where('running_status',$request->filter_ad);
            })
            ->where('deleted_at', '=', null);

            if($request->has('search')){
                $ads->where('ad_ref_id', 'LIKE', "%{$request->search}%")
                ->orWhereHas('products', function ($q) use ($request) {
                        $q->where('name', 'LIKE', "%{$request->search}%");
                });
            }

            //Multiple Filter
            // $Filtred = $helpers->filter($ads, $columns, $param, $request)
            //     // Search With Multiple Param
            // ->where(function ($query) use ($request) {
            //         return $query->when($request->filled('search'), function ($query) use ($request) {
            //             return $query->where('id', 'LIKE', "%{$request->search}%")
            //                 ->orWhere('ad_ref_status', 'LIKE', "%{$request->search}%")
            //                 ->orWhere(function ($query) use ($request) {
            //                     return $query->whereHas('product', function ($q) use ($request) {
            //                         $q->where('name', 'LIKE', "%{$request->search}%");
            //                     });
            //                 })
            //                 ->orWhere(function ($query) use ($request) {
            //                     return $query->whereHas('warehouses', function ($q) use ($request) {
            //                         $q->where('name', 'LIKE', "%{$request->search}%");
            //                     });
            //                 })
            //                 ->orWhere('amount_spent', 'like', "%{$request->search}%")
            //                 ->orWhere('start_date', $request->search)
            //                 ->orWhere('end_date', $request->search);
            //         });
            //     });




            $ads->filter();

            $totalRows = $ads->count();
            if ($perPage == "-1") {
                $perPage = $totalRows;
            }


            // $updateAdsAction->invoke();

            $ads = $ads->offset($offSet)
                ->limit($perPage)->orderBy($order, $dir)->get();



            // $ads = Ad::where('deleted_at',null)->get();
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
            $products = Product::where('deleted_at', '=', null)
            ->whereHas('ads')
            ->get(['id', 'name']);


            return response()->json([
                'totalRows' => $totalRows,
                'ads' => $ads,
                'warehouses' => $warehouses,
                'products' => $products,

            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function ads_report(request $request ){
        try {

            Ad::query();

            $ads_need_upscaling = Ad::query()
            ->where('growth_status','upscale')
            ->where('running_status','completed')
            ->where('is_latest',1)
            ->orderBy('end_date','desc')
            ->get();


            $ads_need_turning_on = Ad::query()
            ->where('is_latest',1)
            ->where('running_status','off')
            ->where(function($q){
                $q->where('preformance_status','success')
                ->orWhere('preformance_status','average');
            })
            ->orderBy('end_date','desc')
            ->get();

            $ads_need_republishing = Ad::query()
            ->where('is_latest',1)
            ->where('running_status','completed')
            ->where(function($q){
                $q->where('preformance_status','success')
                ->orWhere('preformance_status','average');
            })
            ->where(function($q){
                $q->where('growth_status','!=','downscale')
                ->Where('growth_status','!=','upscale');
            })
            ->orderBy('end_date','desc')
            ->get();


            $ads_need_content_update = Ad::query()
            ->where('is_latest',1)
            ->where(function($q){
                $q->where('growth_status','downscale')
                ->orWhere('preformance_status','loser');
            })
            ->where(function($q){
                $q->where('running_status','completed')
                ->orWhere('running_status','off');
            })
            ->orderBy('end_date','desc')
            ->get();



            return response()->json([
                'ads_need_content_update' => $ads_need_content_update,
                'ads_need_turning_on' => $ads_need_turning_on,
                'ads_need_upscaling' => $ads_need_upscaling,
                'ads_need_republishing' => $ads_need_republishing

            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update_ads_data( updateAdsAction $updateAdsAction){
        try {

            $updateAdsAction->invoke();

            return response()->json([
                'message' => 'success',

            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function weeklyChart(request $request){

        $weeks = $this->weeksBetweenTwoDates(Carbon::make('2023-05-01'), Carbon::now());

        $weekly_ad_spend =[];
        $weekly_revenue_from_completed_sale = [];
        $weekly_cost = [];
        $weekly_net_profit = [];

        foreach ($weeks as $week) {

            $weekly_ads = Ad::where('deleted_at',null)
            ->whereDate('start_date','>=',$week['from']->toDateTimeString())
            ->whereDate('end_date','<=',$week['to']->toDateTimeString())
            ->get();

            $weekly_ad_spend[] = $weekly_ads->sum('amount_spent');


            $week_discount = Sale::where('deleted_at',null)
            ->whereDate('date','>=',$week['from']->toDateString())
            ->whereDate('date','<=',$week['to']->toDateString())
            ->where('statut','completed')
            ->get()->sum('discount');


            $weekly_revenue_from_completed_sale[] =

            $weekly_completed_sales = Sale::where('deleted_at',null)
            ->whereDate('date','>=',$week['from']->toDateString())
            ->whereDate('date','<=',$week['to']->toDateString())
            ->where('statut','completed')
            ->get();

            $net_profit =  $weekly_completed_sales->sum('GrandTotal') - ($weekly_completed_sales->sum('sale_cost') - $weekly_ads->sum('amount_spent') );
            $weekly_net_profit[] = $net_profit;

        }

        return response()->json([
            'data' => [
                'weekly_ad_spend' => $weekly_ad_spend,
                'weekly_net_profit' => $weekly_net_profit
            ]
        ]);
    }

    public function weeksBetweenTwoDates($start, $end)
    {
        $weeks = [];

        while ($start->weekOfYear !== $end->weekOfYear) {
            $weeks[] = [
                'from' => $start->startOfWeek(),
                'to' => $start->endOfWeek(),
                'week_of_year' => $start->weekOfYear,
                'week_of_month' => $start->weekOfMonth,
                'month_name' => $start->monthName,
            ];

            $start->addWeek(1);
        }

        return $weeks;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ad  $ad
     * @return \Illuminate\Http\Response
     */
    public function show(request $request, Ad $ad)
    {
        $periods =[];
        $spends =[];
        // dd($ad);

        // foreach ($ad->product->ads as $pre_ad ) {
        //     $periods[] = Carbon::make($pre_ad->start_date)->toDateString()." --> ".Carbon::make($pre_ad->end_date)->toDateString();
        //     $spends[] = $pre_ad->amount_spent;
        // }


        $previous_ads = ProductAd::query()
        ->with('ad')
        ->where('ad_id','!=',$ad->id)
        ->whereIn('product_id',$ad->products->pluck('id')->toArray())
        ->get()
        ->groupBy('ad_id');


        $previous_ads->filter(function($v) use($ad){
            if(count($v) == $ad->products()->count()) {
                return true;
            }else{
                return false;
            }
        });
        dd($previous_ads);

        $previous_ads = $previous_ads->pluck('ad');
        // $previous_ads = $ad->product->ads()->orderBy('end_date','desc')->get();




        return response()->json([
            'ad' => $ad,
            'previous_ads' => $previous_ads,
            'previous_ads_chart'=>[
                'labels' => $previous_ads->map(function($ad){
                    return Carbon::make($ad->end_date)->format('Y-m-d');
                }),
                'costs' => $previous_ads->map(function($ad){
                    return  round($ad->amount_spent,2);
                }),
                'nets' => $previous_ads->map(function($ad){
                    return  round(($ad->completed_sales_profit - $ad->amount_spent),2);
                }),
                'profits' => $previous_ads->map(function($ad){
                    return  round(($ad->completed_sales_profit),2);
                }),
                'no_sales' => $previous_ads->map(function($ad){
                    return ($ad->no_sales);
                }),
                'costs_vs_sales' =>
                $previous_ads->map(function($ad){
                    return [
                        'x' => round($ad->amount_spent,2),
                        'y' => ($ad->no_sales)
                    ];

                }),

            ],
            'product_ads' => [
                'periods' => [],
                'spends' => []

            ]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Ad  $ad
     * @return \Illuminate\Http\Response
     */
    public function edit(Ad $ad)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ad  $ad
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ad $ad)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ad  $ad
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ad $ad)
    {
        //
    }
}
