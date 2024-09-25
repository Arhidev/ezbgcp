<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->increments('id');

            $table->string('channel');

            $table->unsignedInteger('route_id');
            $table->foreign('route_id')->references('id')->on('routes');

            $table->date('effective_date');
            
            $table->unsignedInteger('repetition_period'); //in days, 0 means no repeat

            $table->unsignedInteger('stop_to_stop_avg_time'); //in mins

            $table->time('first_stop_time');
            $table->time('last_stop_time')->nullable();

            $table->unsignedInteger('status_id');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');
            
            $table->unsignedInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('users')->onDelete('set null');
            
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
        Schema::dropIfExists('trips');
    }
}
