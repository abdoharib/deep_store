<?php

use App\Http\Controllers\TenantController;
use App\Http\Middleware\ResolveTenantMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Clue\StreamFilter\fun;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

//------------------------------------------------------------------\\

Route::post('/login', [
    'uses' => 'Auth\LoginController@login',
    'middleware' => 'Is_Active',
]);


Route::get('/update_tenant',function(){
    $columns = 'Tables_in_' . env('DB_DATABASE');//This is just to read the object by its key, DB_DATABASE is database name.
        $tables = DB::select('SHOW TABLES');

        foreach ( $tables as $table ) {
            $table_name = $table->$columns;
            if($table->$columns == 'domains' || $table->$columns == 'tenants' || $table->$columns == 'permissions'){

            }else{
                //todo add it to laravel jobs, process it will queue as it will take time.
                Schema::table($table->$columns, function (Blueprint $table) use($table_name) {

                    if(!Schema::hasColumn($table_name,'tenant_id')){
                        $table->foreignIdFor(Tenant::class)->nullable()->default(1);
                    }
                });

            };
        }
        return response()->json([
            'msg' => '100'
        ],200);
});

//Tenant routes
Route::get('/register',[TenantController::class,'create']);
Route::post('/register',[TenantController::class,'store']);


Route::get('password/find/{token}', 'PasswordResetController@find');



Route::group(['middleware' => ['auth', 'Is_Active']], function () {

    Route::get('/login', function () {
        $installed = Storage::disk('public')->exists('installed');
        if ($installed === false) {
            return redirect('/setup');
        } else {
            return redirect('/login');
        }
    });


    Route::get(
        '/{vue?}',
        function () {
            $installed = Storage::disk('public')->exists('installed');

            if ($installed === false) {
                return redirect('/setup');
            } else {
                return view('layouts.master');
            }
        }
    )->where('vue', '^(?!api|setup|update|password).*$');
});

Auth::routes([
    'register' => false,
]);




//------------------------------------------------------------------\\

Route::group(['middleware' => ['auth', 'Is_Active']], function () {

    Route::get('/update', 'UpdateController@viewStep1');

    Route::get('/update/finish', function () {

        return view('update.finishedUpdate');
    });

    Route::post('/update/lastStep', [
        'as' => 'update_lastStep', 'uses' => 'UpdateController@lastStep',
    ]);
});





















//------------------------------------------------------------------\\

$installed = Storage::disk('public')->exists('installed');

if ($installed === false) {
    Route::get('/setup', [
        'uses' => 'SetupController@viewCheck',
    ])->name('setup');

    Route::get('/setup/step-1', [
        'uses' => 'SetupController@viewStep1',
    ]);

    Route::post('/setup/step-2', [
        'as' => 'setupStep1', 'uses' => 'SetupController@setupStep1',
    ]);

    Route::post('/setup/testDB', [
        'as' => 'testDB', 'uses' => 'TestDbController@testDB',
    ]);

    Route::get('/setup/step-2', [
        'uses' => 'SetupController@viewStep2',
    ]);

    Route::get('/setup/step-3', [
        'uses' => 'SetupController@viewStep3',
    ]);

    Route::get('/setup/finish', function () {

        return view('setup.finishedSetup');
    });

    Route::get('/setup/getNewAppKey', [
        'as' => 'getNewAppKey', 'uses' => 'SetupController@getNewAppKey',
    ]);

    Route::get('/setup/getPassport', [
        'as' => 'getPassport', 'uses' => 'SetupController@getPassport',
    ]);

    Route::get('/setup/getMegrate', [
        'as' => 'getMegrate', 'uses' => 'SetupController@getMegrate',
    ]);

    Route::post('/setup/step-3', [
        'as' => 'setupStep2', 'uses' => 'SetupController@setupStep2',
    ]);

    Route::post('/setup/step-4', [
        'as' => 'setupStep3', 'uses' => 'SetupController@setupStep3',
    ]);

    Route::post('/setup/step-5', [
        'as' => 'setupStep4', 'uses' => 'SetupController@setupStep4',
    ]);

    Route::post('/setup/lastStep', [
        'as' => 'lastStep', 'uses' => 'SetupController@lastStep',
    ]);

    //    Route::get('setup/lastStep', function () {
    //        return redirect('/setup', 301);
    //    });

} else {
    Route::any('/setup/{vue}', function () {
        abort(403);
    });
}

//------------------------------------------------------------------\\
