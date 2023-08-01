<?php

use App\Models\Cycle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCycleVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cycle_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Cycle::class);

            $table->string('campaign_ref_id');
            $table->string('campaign_name');
            $table->string('ver_no')->nullable();

            $table->dateTime('start_date');
            $table->dateTime('end_date');

			$table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cycle_versions');
    }
}
