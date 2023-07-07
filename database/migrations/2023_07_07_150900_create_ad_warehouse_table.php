<?php

use App\Models\Ad;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdWarehouseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_warehouse', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Ad::class);
            $table->foreignIdFor(Warehouse::class);

			$table->timestamps(6);
			$table->softDeletes();
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ad_warehouse', function (Blueprint $table) {
            //
        });
    }
}
