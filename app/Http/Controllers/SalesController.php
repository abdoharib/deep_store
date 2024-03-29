<?php

namespace App\Http\Controllers;

use App\actions\createVanexShipmentAction;
use App\actions\getVanexShipmentAction;
use App\actions\sendTelegramMessage;
use App\actions\sendWhatsAppMessage;
use Twilio\Rest\Client as Client_Twilio;
use App\Mail\SaleMail;
use App\Models\Client;
use App\Models\Unit;
use App\Models\PaymentSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Quotation;
use App\Models\Shipment;
use App\Models\sms_gateway;
use App\Models\Role;
use App\Models\SaleReturn;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\PosSetting;
use App\Models\User;
use App\Models\UserWarehouse;
use App\Models\Warehouse;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Stripe;
use App\Models\PaymentWithCreditCard;
// use App\Models\ShippingProvider;
use App\Notifications\NewSaleNotification;
use App\Notifications\SaleStatusUpdateNotification;
// use App\Services\ShippingService;
use DB;
use PDF;
use ArPHP\I18N\Arabic;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;
use \Nwidart\Modules\Facades\Module;

class SalesController extends BaseController
{

    //------------- GET ALL SALES -----------\\

    public function index(request $request, getVanexShipmentAction $getVanexShipmentAction)
    {
        $this->authorizeForUser($request->user(), 'view', Sale::class);

        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;

        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
            3 => 'like',
            4 => '=',
            5 => '=',
            6 => 'like',
            7 => '=',
            8 => '=',
            9 => '=',
            10 => '=',

        );
        $columns = array(
            0 => 'Ref',
            1 => 'statut',
            2 => 'client_id',
            3 => 'payment_statut',
            4 => 'warehouse_id',
            5 => 'date',
            6 => 'shipping_status',
            7 => 'created_at',
            8 => 'answer_status',
            9 => 'has_stock',
            10 => 'shipping_provider',
        );
        $data = array();

        $assignedWarehouses = Auth::user()->assignedWarehouses->pluck('id');
        $assignedWarehouses = $assignedWarehouses->toArray();
        // dd($assignedWarehouses);

        // Check If User Has Permission View  All Records
        $Sales = Sale::with('facture', 'client', 'warehouse','user')
            ->where('deleted_at', '=', null)
            ->where(function($q) use($assignedWarehouses) {
                if(Auth::user()->hasRole('Delivery')){
                    $q->whereIn('warehouse_id', $assignedWarehouses);
                }
            })
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            });


            //Multiple Filter
        $Filtred = $helpers->filter($Sales, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'like', "%{$request->search}%")
                        ->orWhere('shipping_status', 'like', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('details', function ($q) use ($request) {
                                $q->whereHas('product', function ($query) use ($request) {
                                    $query->where('name', 'LIKE', "%{$request->search}%");
                                });
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere('notes', 'LIKE', "%{$request->search}%");

                });
            });

        $totalRows = $Filtred->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }

        $Sales = $Filtred->offset($offSet)
            ->limit($perPage)->orderBy($order, $dir)->get();

        foreach ($Sales as $Sale) {

            $package_details = null;
            //has vanex code
            // if($Sale->shipping_provider == 'vanex' && $Sale->vanex_shipment_code){
            //     //retrive the shipping info
            //     $package_details = $getVanexShipmentAction->invoke($Sale);
            // }

            $item['id'] = $Sale['id'];
            $item['date'] = $Sale['date'];
            $item['seen_at'] = $Sale['seen_at'];

            if (Carbon::now()->diffInMinutes($Sale['created_at']) > 60) {
                if (Carbon::now()->diffInHours($Sale->created_at) > 60) {
                    $item['created_since'] = Carbon::now()->diffInDays($Sale['created_at']);
                    $item['created_since_unit'] = 'أيام';

                } else {
                    $item['created_since'] = Carbon::now()->diffInHours($Sale['created_at']);
                    $item['created_since_unit'] = 'ساعة';

                }
            } else {
                $item['created_since'] = Carbon::now()->diffInMinutes($Sale['created_at']);
                $item['created_since_unit'] = 'دقيقة';
            };

            // $item['created_since'] = Carbon::now()->diffInMinutes($Sale['created_at']);
            $item['postponed_date'] = $Sale['postponed_date'];
            $item['cancel_reason'] = $Sale['cancel_reason'];
            $item['updated_by'] = $Sale['updated_by'];
            $item['notes'] = $Sale['notes'];
            $item['address'] = $Sale['address'];
            $item['delivery_note'] = $Sale['delivery_note'];
            $item['Ref'] = $Sale['Ref'];
            $item['answer_status'] = $Sale['answer_status'];
            $item['created_by'] = $Sale['user']->username;
            $item['created_at'] = $Sale['created_at'];
            $item['sale_details'] = $Sale->details->pluck('product');
            $item['statut'] = $Sale['statut'];
            $item['shipping_status'] =  $Sale['shipping_status'];
            $item['has_stock'] =  $Sale['has_stock'];
            $item['discount'] = $Sale['discount'];
            $item['shipping'] = $Sale['shipping'];
            $item['warehouse_name'] = $Sale['warehouse']['name'];
            $item['client_id'] = $Sale['client']['id'];
            $item['client_name'] = $Sale['client']['name'];
            $item['client_email'] = $Sale['client']['email'];
            $item['client_tele'] = $Sale['client']['phone'];
            $item['client_code'] = $Sale['client']['code'];
            $item['client_adr'] = $Sale['client']['adresse'];
            $item['GrandTotal'] = number_format($Sale['GrandTotal'], 2, '.', '');
            $item['paid_amount'] = number_format($Sale['paid_amount'], 2, '.', '');
            $item['due'] = number_format($item['GrandTotal'] - $item['paid_amount'], 2, '.', '');
            $item['payment_status'] = $Sale['payment_statut'];
            $item['last_vanex_update'] = $Sale['last_vanex_update'];
            $item['vanex_shipment_status'] = $Sale['vanex_shipment_status'];

            if (SaleReturn::where('sale_id', $Sale['id'])->where('deleted_at', '=', null)->exists()) {
                $sellReturn = SaleReturn::where('sale_id', $Sale['id'])->where('deleted_at', '=', null)->first();
                $item['salereturn_id'] = $sellReturn->id;
                $item['sale_has_return'] = 'yes';
            }else{
                $item['sale_has_return'] = 'no';
            }

            $item['shipping_provider'] = $Sale['shipping_provider'];
            $item['vanex_shipment_code'] = $Sale['vanex_shipment_code'];

            // if(!is_null($package_details)){
            //     $item['vanex_shipment_status'] = $package_details['status_object']['status_name_cust'];
            // }else{
            //     $item['vanex_shipment_status'] = '/';
            // }

            $data[] = $item;
        }

        $stripe_key = config('app.STRIPE_KEY');
        // $customers = client::where('deleted_at', '=', null)->get(['id', 'name']);

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }

        return response()->json([
            'stripe_key' => $stripe_key,
            'totalRows' => $totalRows,
            'sales' => $data,
            'customers' => [],
            'warehouses' => $warehouses,
        ]);
    }

    //------------- STORE NEW SALE-----------\\

    public function store(Request $request, createVanexShipmentAction $createVanexShipmentAction)
    {


        $this->authorizeForUser($request->user(), 'create', Sale::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',

            'shipping_provider' => 'required',
            'vanex_city_id' => 'required_if:shippig_provider,2',
            'vanex_sub_city_id' => 'nullable',
            'vanex_shipment_sticker_notes' => 'required_if:shippig_provider,2',
            // 'dont_create_shipment' => 'required_if:shippig_provider,2|integer'

        ]);


        $shipping_provider_mapper = [
            1 => 'local',
            2 => 'vanex'
        ];

        try {
            \DB::transaction(function () use ($request, $createVanexShipmentAction,$shipping_provider_mapper ) {
                $helpers = new helpers();
                $order = new Sale;

                $order->is_pos = 0;
                $order->date = $request->date;
                $order->postponed_date = $request->postponed_date;
                $order->Ref = $this->getNumberOrder();
                $order->client_id = $request->client_id;
                $order->GrandTotal = $request->GrandTotal;
                $order->warehouse_id = $request->warehouse_id;
                $order->tax_rate = $request->tax_rate;
                $order->TaxNet = $request->TaxNet;
                $order->discount = $request->discount;
                $order->shipping = $request->shipping;
                $order->statut = $request->statut;
                $order->payment_statut = 'unpaid';
                $order->notes = $request->notes;
                $order->address = $request->address;
                $order->delivery_note = $request->delivery_note;
                $order->answer_status = $request->answer_status;
                $order->vanex_city_id = $request->vanex_city_id;
                $order->vanex_sub_city_id = $request->vanex_sub_city_id;
                $order->vanex_shipment_sticker_notes = $request->vanex_shipment_sticker_notes;
                $order->shipping_provider = $shipping_provider_mapper[$request->shipping_provider];
                $order->cancel_reason = $request->cancel_reason;
                $order->user_id = Auth::user()->id;
                $order->save();
                // $order->seen_at == $order->created_at;

                $data = $request['details'];
                foreach ($data as $key => $value) {
                    $unit = Unit::where('id', $value['sale_unit_id'])
                        ->first();
                    $orderDetails[] = [
                        'date' => $request->date,
                        'sale_id' => $order->id,
                        'sale_unit_id' =>  $value['sale_unit_id'],
                        'quantity' => $value['quantity'],
                        'price' => $value['Unit_price'],
                        'TaxNet' => $value['tax_percent'],
                        'tax_method' => $value['tax_method'],
                        'discount' => $value['discount'],
                        'discount_method' => $value['discount_Method'],
                        'product_id' => $value['product_id'],
                        'product_variant_id' => $value['product_variant_id'],
                        'total' => $value['subtotal'],
                        'imei_number' => $value['imei_number'],
                    ];


                    if ($order->statut == "completed" || $order->statut == 'under_shipping') {
                        if ($value['product_variant_id'] !== null) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $order->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();

                            if ($unit && $product_warehouse) {
                                if ($unit->operator == '/') {
                                    $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                                } else {
                                    if(($value['quantity'] *  $unit->operator_value) > $product_warehouse->qte){
                                        throw new \Exception('مخزون غير متوفر');
                                    }
                                    $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }
                    }
                }
                foreach ($orderDetails as $orderDetail) {
                    # code...
                    SaleDetail::create($orderDetail);
                }

                $role = Auth::user()->roles()->first();
                $view_records = Role::findOrFail($role->id)->inRole('record_view');

                if ($request->payment['status'] != 'pending') {
                    $sale = Sale::findOrFail($order->id);
                    // Check If User Has Permission view All Records
                    if (!$view_records) {
                        // Check If User->id === sale->id
                        $this->authorizeForUser($request->user(), 'check_record', $sale);
                    }


                    try {

                        $total_paid = $sale->paid_amount + $request['amount'];
                        $due = $sale->GrandTotal - $total_paid;

                        if ($due === 0.0 || $due < 0.0) {
                            $payment_statut = 'paid';
                        } else if ($due != $sale->GrandTotal) {
                            $payment_statut = 'partial';
                        } else if ($due == $sale->GrandTotal) {
                            $payment_statut = 'unpaid';
                        }

                        if($request['amount'] > 0){
                            if($request->payment['Reglement'] == 'credit card'){
                                $Client = Client::whereId($request->client_id)->first();
                                Stripe\Stripe::setApiKey(config('app.STRIPE_SECRET'));

                                $PaymentWithCreditCard = PaymentWithCreditCard::where('customer_id' ,$request->client_id)->first();
                                if(!$PaymentWithCreditCard){
                                    // Create a Customer
                                    $customer = \Stripe\Customer::create([
                                        'source' => $request->token,
                                        'email' => $Client->email,
                                    ]);

                                    // Charge the Customer instead of the card:
                                    $charge = \Stripe\Charge::create([
                                        'amount' => $request['amount'] * 100,
                                        'currency' => 'usd',
                                        'customer' => $customer->id,
                                    ]);
                                    $PaymentCard['customer_stripe_id'] =  $customer->id;

                                }else{
                                    $customer_id = $PaymentWithCreditCard->customer_stripe_id;
                                    $charge = \Stripe\Charge::create([
                                        'amount' => $request['amount'] * 100,
                                        'currency' => 'usd',
                                        'customer' => $customer_id,
                                    ]);
                                    $PaymentCard['customer_stripe_id'] =  $customer_id;
                                }

                                $PaymentSale = new PaymentSale();
                                $PaymentSale->sale_id = $order->id;
                                $PaymentSale->Ref = app('App\Http\Controllers\PaymentSalesController')->getNumberOrder();
                                $PaymentSale->date = Carbon::now();
                                $PaymentSale->Reglement = $request->payment['Reglement'];
                                $PaymentSale->montant = $request['amount'];
                                $PaymentSale->change = $request['change'];
                                $PaymentSale->user_id = Auth::user()->id;
                                $PaymentSale->save();

                                $sale->update([
                                    'paid_amount' => $total_paid,
                                    'payment_statut' => $payment_statut,
                                ]);

                                $PaymentCard['customer_id'] = $request->client_id;
                                $PaymentCard['payment_id'] = $PaymentSale->id;
                                $PaymentCard['charge_id'] = $charge->id;
                                PaymentWithCreditCard::create($PaymentCard);

                            // Paying Method Cash
                            }else{

                                PaymentSale::create([
                                    'sale_id' => $order->id,
                                    'Ref' => app('App\Http\Controllers\PaymentSalesController')->getNumberOrder(),
                                    'date' => Carbon::now(),
                                    'Reglement' => $request->payment['Reglement'],
                                    'montant' => $request['amount'],
                                    'change' => $request['change'],
                                    'user_id' => Auth::user()->id,
                                ]);

                                $sale->update([
                                    'paid_amount' => $total_paid,
                                    'payment_statut' => $payment_statut,
                                ]);
                            }
                        }
                    } catch (Exception $e) {
                        return response()->json(['message' => $e->getMessage()], 500);
                    }

                }

                //only create shippment if the provider is vanex
                // $shippingService->createShipment($order);

                if($request->shipping_provider == 2){
                    // if(!$request->input('dont_create_shipment')){
                        $createVanexShipmentAction->invoke($order);
                    // }
                }

                $order->warehouse->assignedUsers->each(function(User $user) use($order){
                    // dd($order);

                    $user->notify(new NewSaleNotification($order));
                });


            }, 10);
        }catch (\Exception $e){
            return abort(500, $e->getMessage());

        }



        return response()->json(['success' => true]);
    }


    //------------- UPDATE SALE -----------

    public function update(Request $request, $id, sendTelegramMessage $sendTelegramMessage)
    {
        $this->authorizeForUser($request->user(), 'update', Sale::class);

        request()->validate([
            'warehouse_id' => 'required',
            'client_id' => 'required',
        ]);

        \DB::transaction(function () use ($request, $id,$sendTelegramMessage) {

            try {
                $role = Auth::user()->roles()->first();
                $view_records = Role::findOrFail($role->id)->inRole('record_view');
                $current_Sale = Sale::findOrFail($id);

                if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                    return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
                }else{
                    // Check If User Has Permission view All Records
                    if (!$view_records) {
                        // Check If User->id === Sale->id
                        $this->authorizeForUser($request->user(), 'check_record', $current_Sale);
                    }
                    $old_sale_details = SaleDetail::where('sale_id', $id)->get();
                    $new_sale_details = $request['details'];
                    $length = sizeof($new_sale_details);

                    // Get Ids for new Details
                    $new_products_id = [];
                    foreach ($new_sale_details as $new_detail) {
                        $new_products_id[] = $new_detail['id'];
                    }

                    // Init Data with old Parametre
                    $old_products_id = [];
                    foreach ($old_sale_details as $key => $value) {
                        $old_products_id[] = $value->id;

                        //check if detail has sale_unit_id Or Null
                        if($value['sale_unit_id'] !== null){
                            $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                        }else{
                            $product_unit_sale_id = Product::with('unitSale')
                            ->where('id', $value['product_id'])
                            ->first();
                            $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                        }

                        if($value['sale_unit_id'] !== null){
                            if ($current_Sale->statut == "completed" || ($current_Sale->statut == "under_shipping") ) {

                                if ($value['product_variant_id'] !== null) {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $current_Sale->warehouse_id)
                                        ->where('product_id', $value['product_id'])
                                        ->where('product_variant_id', $value['product_variant_id'])
                                        ->first();

                                    if ($product_warehouse) {
                                        if ($old_unit->operator == '/') {
                                            $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                        } else {
                                            $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                        }
                                        $product_warehouse->save();
                                    }

                                } else {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $current_Sale->warehouse_id)
                                        ->where('product_id', $value['product_id'])
                                        ->first();
                                    if ($product_warehouse) {

                                        if ($old_unit->operator == '/') {
                                            $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                        } else {
                                            // dd($product_warehouse->qte);

                                            $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                        }
                                        $product_warehouse->save();
                                    }
                                }
                            }
                            // Delete Detail
                            if (!in_array($old_products_id[$key], $new_products_id)) {
                                $SaleDetail = SaleDetail::findOrFail($value->id);
                                $SaleDetail->delete();
                            }
                        }
                    }

                    // Update Data with New request
                    foreach ($new_sale_details as $prd => $prod_detail) {

                        if($prod_detail['no_unit'] !== 0){
                            $unit_prod = Unit::where('id', $prod_detail['sale_unit_id'])->first();

                            if ( ($request['statut'] == "completed") || ($request['statut'] == "under_shipping") ) {

                                if ($prod_detail['product_variant_id'] !== null) {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $request->warehouse_id)
                                        ->where('product_id', $prod_detail['product_id'])
                                        ->where('product_variant_id', $prod_detail['product_variant_id'])
                                        ->first();

                                    if ($product_warehouse) {
                                        if ($unit_prod->operator == '/') {
                                            $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                        } else {
                                            $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                        }
                                        $product_warehouse->save();
                                    }

                                } else {
                                    $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                        ->where('warehouse_id', $request->warehouse_id)
                                        ->where('product_id', $prod_detail['product_id'])
                                        ->first();

                                    if ($product_warehouse) {
                                        if ($unit_prod->operator == '/') {
                                            $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                        } else {
                                            // dd($product_warehouse);
                                            if( ($prod_detail['quantity'] * $unit_prod->operator_value) > $product_warehouse->qte){
                                                throw new \Exception('مخزون غير متوفر');
                                            }
                                            $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                        }

                                        $product_warehouse->save();
                                    }
                                }

                            }

                            $orderDetails['sale_id'] = $id;
                            $orderDetails['date'] = $request['date'];
                            $orderDetails['price'] = $prod_detail['Unit_price'];
                            $orderDetails['sale_unit_id'] = $prod_detail['sale_unit_id'];
                            $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                            $orderDetails['tax_method'] = $prod_detail['tax_method'];
                            $orderDetails['discount'] = $prod_detail['discount'];
                            $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                            $orderDetails['quantity'] = $prod_detail['quantity'];
                            $orderDetails['product_id'] = $prod_detail['product_id'];
                            $orderDetails['product_variant_id'] = $prod_detail['product_variant_id'];
                            $orderDetails['total'] = $prod_detail['subtotal'];
                            $orderDetails['imei_number'] = $prod_detail['imei_number'];

                            if (!in_array($prod_detail['id'], $old_products_id)) {
                                $orderDetails['date'] = Carbon::now();
                                $orderDetails['sale_unit_id'] = $unit_prod ? $unit_prod->id : Null;
                                SaleDetail::Create($orderDetails);
                            } else {
                                SaleDetail::where('id', $prod_detail['id'])->update($orderDetails);
                            }
                        }
                    }




                    $due = $request['GrandTotal'] - $current_Sale->paid_amount;
                    if ($due === 0.0 || $due < 0.0) {
                        $payment_statut = 'paid';
                    } else if ($due != $request['GrandTotal']) {
                        $payment_statut = 'partial';
                    } else if ($due == $request['GrandTotal']) {
                        $payment_statut = 'unpaid';
                    }

                    $old_status = $current_Sale->statut;
                    $old_answer_status = $current_Sale->answer_status;
                    $old_delivery_note = $current_Sale->delivery_note;

                    $current_Sale->update([
                        'date' => $request['date'],
                        'updated_by' => ($old_status !== $request['statut']) ? Auth::user()->email: null,
                        'cancel_reason' => $request['cancel_reason'],
                        'postponed_date' => $request['postponed_date'],
                        'client_id' => $request['client_id'],
                        'warehouse_id' => $request['warehouse_id'],
                        'notes' => $request['notes'],
                        'statut' => $request['statut'],
                        'delivery_note' => $request['delivery_note'],
                        'tax_rate' => $request['tax_rate'],
                        'answer_status' => $request['answer_status'],
                        'TaxNet' => $request['TaxNet'],
                        'address' =>  $request['address'],
                        'discount' => $request['discount'],
                        'shipping' => $request['shipping'],
                        'GrandTotal' => $request['GrandTotal'],
                        'payment_statut' => $payment_statut,
                    ]);

                    $current_Sale->client->update([
                        'backup_phone' => $request['backup_phone'],
                    ]);


                    //status updated
                    if($old_status !== $request['statut'] ){



                        // dd($current_Sale->warehouse->assignedUsers);
                        $current_Sale->warehouse->assignedUsers->each(function(User $user) use($current_Sale){

                            $user->notify(new SaleStatusUpdateNotification($current_Sale));
                        });
                    }

                    if($request['answer_status'] != $old_answer_status){
                        if($request['answer_status'] == 'no_answer'){
                            $sendTelegramMessage->invoke(
                                '-1001661327002','
                            لايوجد أستجابة ❌
                            رقم الهاتف : '.$current_Sale->client->phone.'
                            رقم الطلبية : '.$current_Sale->Ref.'
                            المستودع : '.$current_Sale->warehouse->name.'

                            ');
                        }
                    }

                    if ($request['delivery_note'] != $old_delivery_note) {

                        $sendTelegramMessage->invoke(
                            '-1001661327002',
                            '
                             :📝 ملاحظة مندوب
                             '
                             .
                             $current_Sale->delivery_note
                             .
                             '
                            رقم الهاتف : ' . $current_Sale->client->phone . '
                            رقم الطلبية : ' . $current_Sale->Ref . '
                            المستودع : ' . $current_Sale->warehouse->name . '

                            '
                        );
                    }
                }
            } catch (\Exception $e) {
                return abort(500, $e->getMessage());
            }


        }, 10);

        return response()->json(['success' => true]);
    }

    //------------- Remove SALE BY ID -----------\\

     public function destroy(Request $request, $id)
     {
         $this->authorizeForUser($request->user(), 'delete', Sale::class);

         \DB::transaction(function () use ($id, $request) {
             $role = Auth::user()->roles()->first();
             $view_records = Role::findOrFail($role->id)->inRole('record_view');
             $current_Sale = Sale::findOrFail($id);
             $old_sale_details = SaleDetail::where('sale_id', $id)->get();
             $shipment_data =  Shipment::where('sale_id', $id)->first();

             if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
                return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
            }else{

                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === Sale->id
                    $this->authorizeForUser($request->user(), 'check_record', $current_Sale);
                }
                foreach ($old_sale_details as $key => $value) {

                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }

                    if ($current_Sale->statut == "completed") {

                        if ($value['product_variant_id'] !== null) {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->where('product_variant_id', $value['product_variant_id'])
                                ->first();

                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }
                    }

                }

                if($shipment_data){
                    $shipment_data->delete();
                }
                $current_Sale->details()->delete();
                $current_Sale->update([
                    'deleted_at' => Carbon::now(),
                    'shipping_status' => NULL,
                ]);


                $Payment_Sale_data = PaymentSale::where('sale_id', $id)->get();
                foreach($Payment_Sale_data as $Payment_Sale){
                    if($Payment_Sale->Reglement == 'credit card') {
                        $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                        if($PaymentWithCreditCard){
                            $PaymentWithCreditCard->delete();
                        }
                    }
                    $Payment_Sale->delete();
                }
            }

         }, 10);

         return response()->json(['success' => true]);
     }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user(), 'delete', Sale::class);

        \DB::transaction(function () use ($request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $sale_id) {

                if (SaleReturn::where('sale_id', $sale_id)->where('deleted_at', '=', null)->exists()) {
                    return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
                }else{
                    $current_Sale = Sale::findOrFail($sale_id);
                    $old_sale_details = SaleDetail::where('sale_id', $sale_id)->get();
                    $shipment_data =  Shipment::where('sale_id', $sale_id)->first();

                    // Check If User Has Permission view All Records
                    if (!$view_records) {
                        // Check If User->id === current_Sale->id
                        $this->authorizeForUser($request->user(), 'check_record', $current_Sale);
                    }
                    foreach ($old_sale_details as $key => $value) {

                        //check if detail has sale_unit_id Or Null
                        if($value['sale_unit_id'] !== null){
                            $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                        }else{
                            $product_unit_sale_id = Product::with('unitSale')
                            ->where('id', $value['product_id'])
                            ->first();
                            $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                        }

                        if ($current_Sale->statut == "completed") {

                            if ($value['product_variant_id'] !== null) {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->where('product_variant_id', $value['product_variant_id'])
                                    ->first();

                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }
                                    $product_warehouse->save();
                                }

                            } else {
                                $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                    ->where('warehouse_id', $current_Sale->warehouse_id)
                                    ->where('product_id', $value['product_id'])
                                    ->first();
                                if ($product_warehouse) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }
                                    $product_warehouse->save();
                                }
                            }
                        }

                    }

                    if($shipment_data){
                        $shipment_data->delete();
                    }

                    $current_Sale->details()->delete();
                    $current_Sale->update([
                        'deleted_at' => Carbon::now(),
                        'shipping_status' => NULL,
                    ]);


                    $Payment_Sale_data = PaymentSale::where('sale_id', $sale_id)->get();
                    foreach($Payment_Sale_data as $Payment_Sale){
                        if($Payment_Sale->Reglement == 'credit card') {
                            $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                            if($PaymentWithCreditCard){
                                $PaymentWithCreditCard->delete();
                            }
                        }
                        $Payment_Sale->delete();
                    }
                }
            }

        }, 10);

        return response()->json(['success' => true]);
    }


    //---------------- Get Details Sale-----------------\\

    public function show(Request $request, $id)
    {

        $this->authorizeForUser($request->user(), 'view', Sale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = array();

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === sale->id
            $this->authorizeForUser($request->user(), 'check_record', $sale_data);
        }

        $sale_details['Ref'] = $sale_data->Ref;
        $sale_details['date'] = $sale_data->date;
        $sale_details['seen_at'] = $sale_data->seen_at;
        $sale_details['cancel_reason'] = $sale_data->cancel_reason;
        $sale_details['answer_status'] =  $sale_data->answer_status;
        $sale_details['postponed_date'] = $sale_data->postponed_date;
        $sale_details['note'] = $sale_data->notes;
        $sale_details['statut'] = $sale_data->statut;
        $sale_details['warehouse'] = $sale_data['warehouse']->name;
        $sale_details['discount'] = $sale_data->discount;
        $sale_details['shipping'] = $sale_data->shipping;
        $sale_details['tax_rate'] = $sale_data->tax_rate;
        $sale_details['delivery_note'] = $sale_data->delivery_note;
        $sale_details['TaxNet'] = $sale_data->TaxNet;
        $sale_details['client_name'] = $sale_data['client']->name;
        $sale_details['client_phone'] = $sale_data['client']->phone;
        $sale_details['backup_phone'] = $sale_data['client']->backup_phone;

        $sale_details['client_adr'] = $sale_data['client']->adresse;
        $sale_details['client_email'] = $sale_data['client']->email;
        $sale_details['client_tax'] = $sale_data['client']->tax_number;
        $sale_details['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
        $sale_details['paid_amount'] = number_format($sale_data->paid_amount, 2, '.', '');
        $sale_details['due'] = number_format($sale_details['GrandTotal'] - $sale_details['paid_amount'], 2, '.', '');
        $sale_details['payment_status'] = $sale_data->payment_statut;
        $sale_details['shipping_provider'] = $sale_data['shipping_provider'];
        $sale_details['vanex_shipment_code'] = $sale_data['vanex_shipment_code'];
        $sale_details['has_stock'] = $sale_data['has_stock'];
        $sale_details['answer_status'] = $sale_data['answer_status'];
        $sale_details['delivery_note'] = $sale_data['delivery_note'];
        $sale_details['address'] = $sale_data['address'];
        $sale_details['created_at'] = $sale_data['created_at'];
        $sale_details['updated_at'] = $sale_data['updated_at'];
        $sale_details['updated_by'] = $sale_data['updated_by'];


        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            $sellReturn = SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->first();
            $sale_details['salereturn_id'] = $sellReturn->id;
            $sale_details['sale_has_return'] = 'yes';
        }else{
            $sale_details['sale_has_return'] = 'no';
        }

        foreach ($sale_data['details'] as $detail) {

             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

            } else {
                $data['code'] = $detail['product']['code'];
            }

            $data['id'] = $detail->id;
            $data['quantity'] = $detail->quantity;
            $data['total'] = $detail->total;
            $data['name'] = $detail['product']['name'];
            $data['price'] = $detail->price;
            $data['unit_sale'] = $unit->ShortName;

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = $detail->discount;
            } else {
                $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = $detail->price;
            $data['discount'] = $detail->discount;

            if ($detail->tax_method == '1') {
                // $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['Net_price'] = $detail->price;

                $data['taxe'] = $tax_price;
            } else {
                // $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['Net_price'] = ($detail->price) / (($detail->TaxNet / 100) + 1);

                $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $company = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'details' => $details,
            'sale' => $sale_details,
            'company' => $company,
        ]);

    }

    //-------------- Print Invoice ---------------\\

    public function Print_Invoice_POS(Request $request, $id)
    {
        $helpers = new helpers();
        $details = array();

        $sale = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $item['id'] = $sale->id;
        $item['Ref'] = $sale->Ref;
        $item['date'] = $sale->date;
        $item['discount'] = number_format($sale->discount, 2, '.', '');
        $item['shipping'] = number_format($sale->shipping, 2, '.', '');
        $item['taxe'] =     number_format($sale->TaxNet, 2, '.', '');
        $item['tax_rate'] = $sale->tax_rate;
        $item['client_name'] = $sale['client']->name;
        $item['GrandTotal'] = number_format($sale->GrandTotal, 2, '.', '');
        $item['paid_amount'] = number_format($sale->paid_amount, 2, '.', '');

        foreach ($sale['details'] as $detail) {

             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    $data['name'] = $productsVariants->name . '-' . $detail['product']['name'];

                } else {
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                }


            $data['quantity'] = number_format($detail->quantity, 2, '.', '');
            $data['total'] = number_format($detail->total, 2, '.', '');
            $data['unit_sale'] = $unit->ShortName;

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $settings = Setting::where('deleted_at', '=', null)->first();
        $pos_settings = PosSetting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        return response()->json([
            'symbol' => $symbol,
            'payments' => $payments,
            'setting' => $settings,
            'pos_settings' => $pos_settings,
            'sale' => $item,
            'details' => $details,
        ]);

    }

    //------------- GET PAYMENTS SALE -----------\\

    public function Payments_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user(), 'view', PaymentSale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Sale = Sale::findOrFail($id);

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Sale->id
            $this->authorizeForUser($request->user(), 'check_record', $Sale);
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })->orderBy('id', 'DESC')->get();

        $due = $Sale->GrandTotal - $Sale->paid_amount;

        return response()->json(['payments' => $payments, 'due' => $due]);

    }

    //------------- Reference Number Order SALE -----------\\

    public function getNumberOrder()
    {

        $last = DB::table('sales')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'SL_1111';
        }
        return $code;
    }

    //------------- SALE PDF -----------\\

    public function Sale_PDF(Request $request, $id)
    {

        $details = array();
        $helpers = new helpers();
        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $sale['client_name'] = $sale_data['client']->name;
        $sale['client_phone'] = $sale_data['client']->phone;
        $sale['client_adr'] = $sale_data['client']->adresse;
        $sale['client_email'] = $sale_data['client']->email;
        $sale['client_tax'] = $sale_data['client']->tax_number;
        $sale['TaxNet'] = number_format($sale_data->TaxNet, 2, '.', '');
        $sale['discount'] = number_format($sale_data->discount, 2, '.', '');
        $sale['shipping'] = number_format($sale_data->shipping, 2, '.', '');
        $sale['statut'] = $sale_data->statut;
        $sale['Ref'] = $sale_data->Ref;
        $sale['date'] = $sale_data->date;
        $sale['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
        $sale['paid_amount'] = number_format($sale_data->paid_amount, 2, '.', '');
        $sale['due'] = number_format($sale['GrandTotal'] - $sale['paid_amount'], 2, '.', '');
        $sale['payment_status'] = $sale_data->payment_statut;

        $detail_id = 0;
        foreach ($sale_data['details'] as $detail) {

            //check if detail has sale_unit_id Or Null
            if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
            } else {
                $data['code'] = $detail['product']['code'];
            }

                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = number_format($detail->quantity, 2, '.', '');
                $data['total'] = number_format($detail->total, 2, '.', '');
                $data['name'] = $detail['product']['name'];
                $data['unitSale'] = $unit->ShortName;
                $data['price'] = number_format($detail->price, 2, '.', '');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = number_format($detail->price, 2, '.', '');
            $data['discount'] = number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = number_format($tax_price, 2, '.', '');
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
            }

            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            $details[] = $data;
        }
        $settings = Setting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        $Html = view('pdf.sale_pdf', [
            'symbol' => $symbol,
            'setting' => $settings,
            'sale' => $sale,
            'details' => $details,
        ])->render();

        $arabic = new Arabic();
        $p = $arabic->arIdentify($Html);

        for ($i = count($p)-1; $i >= 0; $i-=2) {
            $utf8ar = $arabic->utf8Glyphs(substr($Html, $p[$i-1], $p[$i] - $p[$i-1]));
            $Html = substr_replace($Html, $utf8ar, $p[$i-1], $p[$i] - $p[$i-1]);
        }

        $pdf = PDF::loadHTML($Html);
        return $pdf->download('sale.pdf');

    }

    //----------------Show Form Create Sale ---------------\\

    public function create(Request $request)
    {

        $this->authorizeForUser($request->user(), 'create', Sale::class);

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }

        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $stripe_key = config('app.STRIPE_KEY');

        return response()->json([
            'stripe_key' => $stripe_key,
            'clients' => $clients,
            // 'shipping_providers' => ShippingProvider::with('shippingCompany')->get(),
            'warehouses' => $warehouses,
        ]);

    }

      //------------- Show Form Edit Sale -----------\\

      public function edit(Request $request, $id)
      {
        if (SaleReturn::where('sale_id', $id)->where('deleted_at', '=', null)->exists()) {
            return response()->json(['success' => false , 'Return exist for the Transaction' => false], 403);
        }else{
          $this->authorizeForUser($request->user(), 'update', Sale::class);
          $role = Auth::user()->roles()->first();
          $view_records = Role::findOrFail($role->id)->inRole('record_view');
          $Sale_data = Sale::with('details.product.unitSale')
              ->where('deleted_at', '=', null)
              ->findOrFail($id);
          $details = array();
          // Check If User Has Permission view All Records
          if (!$view_records) {
              // Check If User->id === sale->id
              $this->authorizeForUser($request->user(), 'check_record', $Sale_data);
          }

          if ($Sale_data->client_id) {
              if (Client::where('id', $Sale_data->client_id)
                  ->where('deleted_at', '=', null)
                  ->first()) {
                  $sale['client_id'] = $Sale_data->client_id;
              } else {
                  $sale['client_id'] = '';
              }
          } else {
              $sale['client_id'] = '';
          }

          if ($Sale_data->warehouse_id) {
              if (Warehouse::where('id', $Sale_data->warehouse_id)
                  ->where('deleted_at', '=', null)
                  ->first()) {
                  $sale['warehouse_id'] = $Sale_data->warehouse_id;
              } else {
                  $sale['warehouse_id'] = '';
              }
          } else {
              $sale['warehouse_id'] = '';
          }

          $sale['GrandTotal'] = $Sale_data->GrandTotal;
          $sale['Ref'] = $Sale_data->Ref;
          $sale['client_phone'] = $Sale_data->client->phone;

          $sale['date'] = $Sale_data->date;

          $is_user_delivery = Auth::user()->assignedWarehouses->contains('id',$Sale_data->warehouse_id) && Auth::user()->hasRole("Delivery");
          if($is_user_delivery){
            $Sale_data->update([
                'seen_at' =>now()
            ]);

            $sale['seen_at'] = $Sale_data->seen_at;
          }

          $sale['postponed_date'] = $Sale_data->postponed_date;

          if(Carbon::now()->diffInMinutes($Sale_data->created_at) > 60){
            if(Carbon::now()->diffInHours($Sale_data->created_at) > 60){
                $sale['created_since'] = Carbon::now()->diffInDays($Sale_data->created_at);
                $sale['created_since_unit'] = 'أيام';

            }else{
                $sale['created_since'] = Carbon::now()->diffInHours($Sale_data->created_at);
                $sale['created_since_unit'] = 'ساعة';
            }
          }else{
            $sale['created_since'] = Carbon::now()->diffInMinutes($Sale_data->created_at);
            $sale['created_since_unit'] = 'دقيقة';

          };

          $sale['tax_rate'] = $Sale_data->tax_rate;
          $sale['TaxNet'] = $Sale_data->TaxNet;
          $sale['discount'] = $Sale_data->discount;
          $sale['shipping'] = $Sale_data->shipping;
          $sale['statut'] = $Sale_data->statut;
          $sale['notes'] = $Sale_data->notes;
          $sale['id'] = $Sale_data->id;
          $sale['delivery_note'] = $Sale_data->delivery_note;
          $sale['address'] = $Sale_data->address;
          $sale['answer_status'] = $Sale_data->answer_status;
          $sale['has_stock'] = $Sale_data->has_stock;
          $sale['backup_phone'] = $Sale_data->client->backup_phone;

          $sale['cancel_reason'] = $Sale_data->cancel_reason;

          $detail_id = 0;
          foreach ($Sale_data['details'] as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                    $data['no_unit'] = 1;
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $detail->product_id)
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    $data['no_unit'] = 0;
                }

              if ($detail->product_variant_id) {
                  $item_product = product_warehouse::where('product_id', $detail->product_id)
                      ->where('deleted_at', '=', null)
                      ->where('product_variant_id', $detail->product_variant_id)
                      ->where('warehouse_id', $Sale_data->warehouse_id)
                      ->first();

                  $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                      ->where('id', $detail->product_variant_id)->first();

                  $item_product ? $data['del'] = 0 : $data['del'] = 1;
                  $data['product_variant_id'] = $detail->product_variant_id;
                  $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

                  if ($unit && $unit->operator == '/') {
                      $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                  } else if ($unit && $unit->operator == '*') {
                      $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                  } else {
                      $data['stock'] = 0;
                  }

              } else {

                  $item_product = product_warehouse::where('product_id', $detail->product_id)
                      ->where('deleted_at', '=', null)->where('warehouse_id', $Sale_data->warehouse_id)
                      ->where('product_variant_id', '=', null)->first();

                  $item_product ? $data['del'] = 0 : $data['del'] = 1;
                  $data['product_variant_id'] = null;
                  $data['code'] = $detail['product']['code'];

                  if ($unit && $unit->operator == '/') {
                      $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                      $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                  } else {
                      $data['stock'] = 0;
                  }

                }

                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
                $data['product_id'] = $detail->product_id;
                $data['has_stock'] = $detail->has_stock;

                $data['total'] = $detail->total;
                $data['name'] = $detail['product']['name'];
                $data['quantity'] = $detail->quantity;
                $data['qte_copy'] = $detail->quantity;
                $data['etat'] = 'current';
                $data['unitSale'] = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;
                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }

                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;

                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {
                    // $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['Net_price'] = $detail->price;

                    $data['taxe'] = $tax_price;
                    // $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                    $data['subtotal'] = ( ($data['Net_price'] * $data['quantity']) - $data['DiscountNet'])
                     + ($tax_price * $data['quantity']);

                } else {
                    // $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['Net_price'] = ($detail->price) / (($detail->TaxNet / 100) + 1);

                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

               $details[] = $data;
          }

         //get warehouses assigned to user
        $user_auth = auth()->user();
        if($user_auth->is_all_warehouses){
            $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        }else{
            $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
            $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
        }

          $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);

          return response()->json([
              'details' => $details,
              'sale' => $sale,
              'clients' => $clients,
              'warehouses' => $warehouses,
          ]);
        }

      }


    //------------- SEND SALE TO EMAIL -----------\\

    public function Send_Email(Request $request)
    {
        $this->authorizeForUser($request->user(), 'view', Sale::class);

        $sale['id'] = $request->id;
        $sale['Ref'] = $request->Ref;
        $settings = Setting::where('deleted_at', '=', null)->first();
        $sale['company_name'] = $settings->CompanyName;
        $pdf = $this->Sale_PDF($request, $sale['id']);
        $this->Set_config_mail(); // Set_config_mail => BaseController
        $mail = Mail::to($request->to)->send(new SaleMail($sale, $pdf));
        return $mail;
    }

    //------------- Show Form Convert To Sale -----------\\

    public function Elemens_Change_To_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user(), 'update', Quotation::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Quotation = Quotation::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);
        $details = array();
        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Quotation->id
            $this->authorizeForUser($request->user(), 'check_record', $Quotation);
        }

        if ($Quotation->client_id) {
            if (Client::where('id', $Quotation->client_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['client_id'] = $Quotation->client_id;
            } else {
                $sale['client_id'] = '';
            }
        } else {
            $sale['client_id'] = '';
        }

        if ($Quotation->warehouse_id) {
            if (Warehouse::where('id', $Quotation->warehouse_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['warehouse_id'] = $Quotation->warehouse_id;
            } else {
                $sale['warehouse_id'] = '';
            }
        } else {
            $sale['warehouse_id'] = '';
        }

        $sale['date'] = $Quotation->date;
        $sale['TaxNet'] = $Quotation->TaxNet;
        $sale['tax_rate'] = $Quotation->tax_rate;
        $sale['discount'] = $Quotation->discount;
        $sale['shipping'] = $Quotation->shipping;
        $sale['statut'] = 'pending';
        $sale['notes'] = $Quotation->notes;

        $detail_id = 0;
        foreach ($Quotation['details'] as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('deleted_at', '=', null)
                        ->first();
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->where('deleted_at', null)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('product_variant_id', '=', null)
                        ->where('deleted_at', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
                }

                $data['id'] = $id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['total'] = $detail->total;
                $data['name'] = $detail['product']['name'];
                $data['etat'] = 'current';
                $data['qte_copy'] = $detail->quantity;
                $data['unitSale'] = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;

                $data['is_imei'] = $detail['product']['is_imei'];
                $data['imei_number'] = $detail->imei_number;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }
                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {
                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

                $details[] = $data;
            }
        }

       //get warehouses assigned to user
       $user_auth = auth()->user();
       if($user_auth->is_all_warehouses){
           $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
       }else{
           $warehouses_id = UserWarehouse::where('user_id', $user_auth->id)->pluck('warehouse_id')->toArray();
           $warehouses = Warehouse::where('deleted_at', '=', null)->whereIn('id', $warehouses_id)->get(['id', 'name']);
       }

        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'details' => $details,
            'sale' => $sale,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);

    }

    //-------------------Sms Notifications -----------------\\

    public function Send_SMS(Request $request)
    {
        $sale = Sale::with('client')->where('deleted_at', '=', null)->findOrFail($request->id);
        $settings = Setting::where('deleted_at', '=', null)->first();
        $gateway = sms_gateway::where('id' , $settings->sms_gateway)
        ->where('deleted_at', '=', null)->first();

        $url = url('/api/sale_pdf/' . $request->id);
        $receiverNumber = $sale['client']->phone;
        $message = "Dear" .' '.$sale['client']->name." \n We are contacting you in regard to a invoice #".$sale->Ref.' '.$url.' '. "that has been created on your account. \n We look forward to conducting future business with you.";

        //twilio
        if($gateway->title == "twilio"){
            try {

                $account_sid = env("TWILIO_SID");
                $auth_token = env("TWILIO_TOKEN");
                $twilio_number = env("TWILIO_FROM");

                $client = new Client_Twilio($account_sid, $auth_token);
                $client->messages->create($receiverNumber, [
                    'from' => $twilio_number,
                    'body' => $message]);

            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        //nexmo
        }elseif($gateway->title == "nexmo"){
            try {

                $basic  = new \Nexmo\Client\Credentials\Basic(env("NEXMO_KEY"), env("NEXMO_SECRET"));
                $client = new \Nexmo\Client($basic);
                $nexmo_from = env("NEXMO_FROM");

                $message = $client->message()->send([
                    'to' => $receiverNumber,
                    'from' => $nexmo_from,
                    'text' => $message
                ]);

            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        }

    }



    //------------------- get_Products_by_sale -----------------\\

    public function get_Products_by_sale(Request $request , $id)
    {

        $this->authorizeForUser($request->user(), 'create', SaleReturn::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $SaleReturn = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = array();

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === SaleReturn->id
            $this->authorizeForUser($request->user(), 'check_record', $SaleReturn);
        }

        $Return_detail['client_id'] = $SaleReturn->client_id;
        $Return_detail['warehouse_id'] = $SaleReturn->warehouse_id;
        $Return_detail['sale_id'] = $SaleReturn->id;
        $Return_detail['tax_rate'] = 0;
        $Return_detail['TaxNet'] = 0;
        $Return_detail['discount'] = 0;
        $Return_detail['shipping'] = 0;
        $Return_detail['statut'] = "received";
        $Return_detail['notes'] = "";

        $detail_id = 0;
        foreach ($SaleReturn['details'] as $detail) {

            //check if detail has sale_unit_id Or Null
            if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
                $data['no_unit'] = 1;
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                $data['no_unit'] = 0;
            }

            if ($detail->product_variant_id) {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('product_variant_id', $detail->product_variant_id)
                    ->where('deleted_at', '=', null)
                    ->where('warehouse_id', $SaleReturn->warehouse_id)
                    ->first();

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $item_product ? $data['del'] = 0 : $data['del'] = 1;
                $data['product_variant_id'] = $detail->product_variant_id;
                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

                if ($unit && $unit->operator == '/') {
                    $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                } else if ($unit && $unit->operator == '*') {
                    $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                } else {
                    $data['stock'] = 0;
                }

            } else {
                $item_product = product_warehouse::where('product_id', $detail->product_id)
                    ->where('warehouse_id', $SaleReturn->warehouse_id)
                    ->where('deleted_at', '=', null)->where('product_variant_id', '=', null)
                    ->first();

                $item_product ? $data['del'] = 0 : $data['del'] = 1;
                $data['product_variant_id'] = null;
                $data['code'] = $detail['product']['code'];

                if ($unit && $unit->operator == '/') {
                    $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                } else if ($unit && $unit->operator == '*') {
                    $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                } else {
                    $data['stock'] = 0;
                }

            }

            $data['id'] = $detail->id;
            $data['detail_id'] = $detail_id += 1;
            $data['quantity'] = $detail->quantity;
            $data['sale_quantity'] = $detail->quantity;
            $data['product_id'] = $detail->product_id;
            $data['name'] = $detail['product']['name'];
            $data['unitSale'] = $unit->ShortName;
            $data['sale_unit_id'] = $unit->id;
            $data['is_imei'] = $detail['product']['is_imei'];
            $data['imei_number'] = $detail->imei_number;

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = $detail->discount;
            } else {
                $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = $detail->price;
            $data['tax_percent'] = $detail->TaxNet;
            $data['tax_method'] = $detail->tax_method;
            $data['discount'] = $detail->discount;
            $data['discount_Method'] = $detail->discount_method;

            if ($detail->tax_method == '1') {

                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = $tax_price;
                $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
            }

            $details[] = $data;
        }


        return response()->json([
            'details' => $details,
            'sale_return' => $Return_detail,
        ]);

    }

}
