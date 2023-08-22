<?php

namespace App\Http\Controllers;

use App\Models\SalesSettlement;
use App\Models\Transaction;
use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as FacadesRequest;

class SalesSettlementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $this->authorizeForUser($request->user(), 'view', SalesSettlement::class);

        $perPage = 40;

        $pageStart = FacadesRequest::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = 'id';
        $dir = 'asc';
        $salesSettlements = SalesSettlement::query()

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $salesSettlements->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $salesSettlements = $salesSettlements->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'salesSettlements' => $salesSettlements,
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
    // public function store(Request $request)
    // {
    //     request()->validate([
    //         'name' => 'required',
    //         'initial_balance' => 'required',
    //     ]);

    //     \DB::transaction(function () use ($request) {

    //         $SalesSettlement = new SalesSettlement();
    //         $SalesSettlement->name = $request['name'];
    //         $SalesSettlement->balance = $request['initial_balance'];
    //         $SalesSettlement->save();


    //     }, 10);

    //     return response()->json(['success' => true]);
    // }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SalesSettlement  $salesSettlement
     * @return \Illuminate\Http\Response
     */
    public function show(SalesSettlement $salesSettlement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SalesSettlement  $salesSettlement
     * @return \Illuminate\Http\Response
     */
    public function edit(SalesSettlement $salesSettlement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SalesSettlement  $salesSettlement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SalesSettlement $salesSettlement)
    {
         // $this->authorizeForUser($request->user(), 'update', Warehouse::class);

         request()->validate([
            'status' => 'required|in:'.SalesSettlement::$CONFIRMED.','.SalesSettlement::$RECIVED,
        ]);

        $must_insert_tx = ($request['status'] == SalesSettlement::$CONFIRMED) && ($salesSettlement->status == SalesSettlement::$RECIVED);
        // dd($must_insert_tx);

        DB::beginTransaction();
        $salesSettlement->update([
            'status' => $request['status'],
        ]);

        $salesSettlement->paymentSales()->update([
            'status' => $request['status'],
        ]);

        if($must_insert_tx){
            Transaction::create([
                'treasury_id' => Treasury::first()->id,
                'amount' => $salesSettlement->amount_recived,
                'is_debit' => 0,
                'document_type' => SalesSettlement::class,
                'document_id' => $salesSettlement->id
            ]);
        }


        DB::commit();


        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SalesSettlement  $salesSettlement
     * @return \Illuminate\Http\Response
     */
    public function destroy(SalesSettlement $salesSettlement)
    {
        //
    }
}
