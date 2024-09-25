<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->increments('id');

            //user
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            //reservation
            $table->unsignedInteger('reservation_id');
            $table->foreign('reservation_id')->references('id')->on('customer_reserved_trips')->onDelete('cascade');

            //complaint
            $table->text('complaint');

            //response
            $table->text('response')->nullable();

            //status
            $table->unsignedInteger('status')->default(0); //0:open, 1:refund, 2:rejected

            //stop id
            $table->unsignedInteger('stop_id')->nullable();
            $table->foreign('stop_id')->references('id')->on('stops');

            //stop name
            $table->string('stop_name')->nullable();

            //stop location
            $table->double('stop_lat')->nullable();
            $table->double('stop_lng')->nullable();

            //customer location (lat, lng)
            $table->double('customer_lat')->nullable();
            $table->double('customer_lng')->nullable();

            //bus location (lat, lng)
            $table->double('bus_lat')->nullable();
            $table->double('bus_lng')->nullable();

            //planned time
            $table->dateTime('planned_time')->nullable();

            //actual time
            $table->dateTime('actual_time')->nullable();

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
        Schema::dropIfExists('complaints');
    }
}
