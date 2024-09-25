<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteStopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->increments('id');

            
            $table->unsignedInteger('stop_id');
            $table->foreign('stop_id')->references('id')->on('stops')->onDelete('cascade');

            $table->unsignedInteger('route_id');
            $table->foreign('route_id')->references('id')->on('routes')->onDelete('cascade');

            $table->unsignedInteger('order');

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
        Schema::dropIfExists('route_stops');
    }
}
