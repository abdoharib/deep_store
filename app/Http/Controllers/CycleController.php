<?php

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\CycleVersion;
use App\Models\Role;
use App\utils\helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;

class CycleController extends Controller
{
    public function index(request $request)
    {
        try {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            // How many items do you want to display.
            $perPage = $request->limit;

            $pageStart = FacadesRequest::get('page', 1);
            // Start displaying items from this number;
            $offSet = ($pageStart * $perPage) - $perPage;
            $order = 'cycle_no';
            $dir = 'asc';
            $helpers = new helpers();
            // Filter fields With Params to retrieve
            $param = array(
                // 0 => '=',
                // 1 => '=',
                // 2 => '=',

            );

            $columns = array(
                // 0 => 'start_date',
                // 1 => 'end_date',
                // 2 => 'ad_ref_status',
            );
            $data = array();

            $cycles = Cycle::where('deleted_at', '=', null)->with(['cycleVersions' => function($q){
                $q->orderBy('ver_no','desc');
            }]);

            //Multiple Filter
            $Filtred = $helpers->filter($cycles, $columns, $param, $request);
                // Search With Multiple Param
            // ->where(function ($query) use ($request) {
            //         // return $query->when($request->filled('search'), function ($query) use ($request) {
            //         //     return $query->where('id', 'LIKE', "%{$request->search}%")
            //         //         ->orWhere('ad_ref_status', 'LIKE', "%{$request->search}%")
            //         //         ->orWhere(function ($query) use ($request) {
            //         //             return $query->whereHas('product', function ($q) use ($request) {
            //         //                 $q->where('name', 'LIKE', "%{$request->search}%");
            //         //             });
            //         //         })
            //         //         ->orWhere(function ($query) use ($request) {
            //         //             return $query->whereHas('warehouses', function ($q) use ($request) {
            //         //                 $q->where('name', 'LIKE', "%{$request->search}%");
            //         //             });
            //         //         })
            //         //         ->orWhere('amount_spent', 'like', "%{$request->search}%")
            //         //         ->orWhere('start_date', $request->search)
            //         //         ->orWhere('end_date', $request->search);
            //         // });
            //     });

            $totalRows = $cycles->count();
            if ($perPage == "-1") {
                $perPage = $totalRows;
            }



            $cycles = $Filtred->offset($offSet)
                ->limit($perPage)->orderBy($order, $dir)->get();


            return response()->json([
                'totalRows' => $totalRows,
                'cycles' => $cycles,
                'status' => [
                    'active',
                    'inactive'
                ],
                // 'warehouses' => $warehouses,
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function show(request $request, CycleVersion $cycleVersion ){
        try {
            dd($cycleVersion);

            return response()->json([
                'cycleVersion' => $cycleVersion,
                'ads'=> $cycleVersion->ads
            ]);
        } catch (\Exception $e) {
            dd($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
