<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('ad_ref_id');
            $table->string('ad_ref_status')->nullable();

            $table->string('product_name')->nullable();
            $table->string('warehouse_name')->nullable();

            $table->foreignIdFor(Product::class);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('closed_at')->nullable();

            $table->double('amount_spent');
            // $table->string('preformance_status')->nullable();

            $table->integer('no_sales')->default(0);
            $table->integer('no_completed_sales')->default(0);
            $table->double("completed_sales_profit")->default(0);

			$table->timestamps(6);
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ads', function (Blueprint $table) {
            //
        });
    }
}
