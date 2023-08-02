<?php

namespace App\Http\Controllers;

use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;

class TreasuryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // $this->authorizeForUser($request->user(), 'view', Treasury::class);

        $perPage = $request->limit;

        $pageStart = FacadesRequest::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = 'id';
        $dir = 'asc';
        $treasuries = Treasury::query()

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $treasuries->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $treasuries = $treasuries->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'treasuries' => $treasuries,
            'totalRows' => $totalRows,
        ]);
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

        request()->validate([
            'name' => 'required',
            'initial_balance' => 'required',
        ]);

        \DB::transaction(function () use ($request) {

            $Treasury = new Treasury();
            $Treasury->name = $request['name'];
            $Treasury->balance = $request['initial_balance'];
            $Treasury->save();


        }, 10);

        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Treasury  $treasury
     * @return \Illuminate\Http\Response
     */
    public function show(Treasury $treasury)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Treasury  $treasury
     * @return \Illuminate\Http\Response
     */
    public function edit(Treasury $treasury)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Treasury  $treasury
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Treasury $treasury)
    {
        // $this->authorizeForUser($request->user(), 'update', Warehouse::class);

        request()->validate([
            'name' => 'required',
        ]);

        $treasury->update([
            'name' => $request['name'],
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Treasury  $treasury
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Treasury $treasury)
    {
        // $this->authorizeForUser($request->user(), 'delete', Warehouse::class);

        \DB::transaction(function () {

            $treasury->update([
                'deleted_at' => Carbon::now(),
            ]);


        }, 10);

        return response()->json(['success' => true]);
    }
}
