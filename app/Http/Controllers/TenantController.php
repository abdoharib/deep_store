<?php

namespace App\Http\Controllers;

use App\actions\assignPermissionToRole;
use App\Models\Provider;
use App\Models\Role;
use App\Models\role_user;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantController extends Controller
{

    public function create(Request $request){
        return view('auth.register');
    }

    public function store(Request $request, assignPermissionToRole $assignPermissionToRole){

        $input = $request->validate([

            //store details
            'store_name' => 'required|string',

            //default admin details
            'first_name'=> 'required|string',
            'last_name'=> 'required|string',
            'username'=> 'required|string',
            'email' => 'required|email|string',
            'phone'=> 'required|string',
            'password'=> 'required|string',
        ]);

        try {
            //code...
            DB::beginTransaction();

            //tenant
            $tenant = Tenant::create([
                'store_name'=> $input['store_name'],
                'is_active'=> 1
            ]);
            tenancy()->initialize($tenant);



            //user
            $role = Role::create([
                'name' => 'المالك',
                'label' => 'مالك المتجر',
                'status' => 1,
            ]);
            $user = User::create(array_merge($input,[
                'is_all_warehouses' => 1,

                'firstname' => $input['first_name'],
                'lastname' => $input['last_name'],

                'role_id' => $role->id,
                'password' => Hash::make($input['password']),
            ]));
            role_user::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]);

            Warehouse::create([
                'name' => 'الرئسي (أفتراضي)',
                'is_default' => 1
            ]);

            Provider::create([
                'name' => 'مورد (أفتراضي)'
            ]);

            $assignPermissionToRole->invoke($role);


            Setting::create(
                [
                    'email' => $user->email,
                    'currency_id' => 2,
                    'client_id' => 1,
                    'sms_gateway' => 1,
                    'footer'=> '',
                    'is_invoice_footer' => 0,
                    'invoice_footer' => Null,
                    'warehouse_id' => Null,
                    'CompanyName' => $tenant->store_name,
                    'CompanyPhone' => '',
                    'CompanyAdress' => '',
                    'footer' => '',
                    'developed_by' => 'منصة تجارة',
                    'logo' => '',
                    'default_language' => 'ar',

                ]
            );

            DB::commit();


            return response()->json([
                'success' => true,
                'message' => 'تم أنشاء المتجر بنجاح'
            ],200);

        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => $th->getMessage()
            ],500);

        }
    }

}
