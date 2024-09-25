<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlannedTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planned_trips', function (Blueprint $table) {
            $table->increments('id');

            $table->string('channel');

            $table->unsignedInteger('trip_id');
            $table->foreign('trip_id')->references('id')->on('trips')->onDelete('cascade');

            //route_id
            $table->unsignedInteger('route_id');
            $table->foreign('route_id')->references('id')->on('routes');

            $table->date('planned_date');
            

            //started at
            $table->timestamp('started_at')->nullable()->default(null);
            //ended at
            $table->timestamp('ended_at')->nullable()->default(null);

            //last position in lat and lng
            $table->double('last_position_lat')->nullable()->default(null);
            $table->double('last_position_lng')->nullable()->default(null);

            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            
            //bus_id
            $table->unsignedInteger('bus_id')->nullable();
            $table->foreign('bus_id')->references('id')->on('buses')->onDelete('set null');

            //reserved_seats
            $table->unsignedInteger('reserved_seats')->default(0);

            //unique key for trip_id and planned_date
            $table->unique(['trip_id', 'planned_date']);

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
        Schema::dropIfExists('planned_trips');
    }
}
