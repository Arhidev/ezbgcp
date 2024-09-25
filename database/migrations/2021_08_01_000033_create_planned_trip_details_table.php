<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlannedTripDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planned_trip_details', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('stop_id');
            $table->foreign('stop_id')->references('id')->on('stops');

            $table->unsignedInteger('planned_trip_id');
            $table->foreign('planned_trip_id')->references('id')->on('planned_trips')->onDelete('cascade');

            $table->time('planned_timestamp');
            $table->time('actual_timestamp')->nullable();

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
        Schema::dropIfExists('planned_trip_details');
    }
}
