<?php

namespace App\Http\Controllers;

use App\actions\updateAdsAction;
use App\Models\Ad;
use App\Models\Role;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\utils\helpers;
use Illuminate\Support\Facades\Request as FacadesRequest;

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
            $order = $request->SortField;
            $dir = $request->SortType;
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

            $ads = Ad::where('deleted_at', '=', null);

            //Multiple Filter
            $ads
                // Search With Multiple Param
            ->where(function ($query) use ($request) {
                    return $query->when($request->filled('search'), function ($query) use ($request) {
                        return $query->where('id', 'LIKE', "%{$request->search}%")
                            ->orWhere('ad_ref_status', 'LIKE', "%{$request->search}%")
                            ->orWhere('preformance_status', 'LIKE', "%{$request->search}%")

                            ->orWhere(function ($query) use ($request) {
                                return $query->whereHas('product', function ($q) use ($request) {
                                    $q->where('name', 'LIKE', "%{$request->search}%");
                                });
                            })
                            ->orWhere(function ($query) use ($request) {
                                return $query->whereHas('warehouses', function ($q) use ($request) {
                                    $q->where('name', 'LIKE', "%{$request->search}%");
                                });
                            })
                            ->orWhere('amount_spent', 'like', "%{$request->search}%")
                            ->orWhereDate('start_date', $request->search)
                            ->orWhereDate('end_date', $request->search);
                    });
                });

            $totalRows = $ads->count();
            if ($perPage == "-1") {
                $perPage = $totalRows;
            }


            $updateAdsAction->invoke();

            $ads = $ads->offset($offSet)
                ->limit($perPage)->orderBy($order, $dir)->get();



            // $ads = Ad::where('deleted_at',null)->get();
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);


            return response()->json([
                'totalRows' => $totalRows,
                'ads' => $ads,
                'status' => [
                    'active',
                    'inactive'
                ],
                'warehouses' => $warehouses,
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
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
    public function show(Ad $ad)
    {
        //
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
