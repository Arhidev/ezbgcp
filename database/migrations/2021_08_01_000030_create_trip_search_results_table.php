<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripSearchResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_search_results', function (Blueprint $table) {
            $table->increments('id');

            //user_id
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedInteger('route_id');
            $table->foreign('route_id')->references('id')->on('routes');
            
            $table->unsignedInteger('planned_trip_id');
            $table->foreign('planned_trip_id')->references('id')->on('planned_trips');

            $table->unsignedInteger('start_stop_id');
            $table->foreign('start_stop_id')->references('id')->on('stops');

            $table->unsignedInteger('end_stop_id');
            $table->foreign('end_stop_id')->references('id')->on('stops');

            $table->double('end_point_lat');
            $table->double('end_point_lng');

            $table->double('distance_to_start_stop');
            $table->double('distance_to_end_stop');
            $table->double('distance_to_end_point');

            $table->double('price');
            $table->double('distance');

            $table->text('start_address');
            $table->text('destination_address');

            //planned_start_date
            $table->date('planned_start_date');
            //planned_start_time
            $table->time('planned_start_time');

            $table->text('path');


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
        Schema::dropIfExists('notes');
    }
}
