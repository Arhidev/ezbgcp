<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_details', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('stop_id');
            $table->foreign('stop_id')->references('id')->on('stops');

            $table->unsignedInteger('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips');

            $table->time('planned_timestamp');
            $table->time('actual_timestamp')->nullable();

            $table->unsignedInteger('inter_time');

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
        Schema::dropIfExists('trip_details');
    }
}
