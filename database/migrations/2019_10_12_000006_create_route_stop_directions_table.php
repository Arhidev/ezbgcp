<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteStopDirectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_stop_directions', function (Blueprint $table) {
            $table->increments('id');
            
            $table->unsignedInteger('route_stop_id');
            $table->foreign('route_stop_id')->references('id')->on('route_stops')->onDelete('cascade');

            $table->unsignedInteger('index');

            $table->string('summary');
            $table->text('overview_path');

            $table->boolean('current')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_stop_directions');
    }
}
