<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerReservedTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_reserved_trips', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ticket_number');
            //customer
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedInteger('planned_trip_id');
            $table->foreign('planned_trip_id')->references('id')->on('planned_trips')->onDelete('cascade');

            $table->date('reservation_date');

            $table->unsignedInteger('start_stop_id');
            $table->foreign('start_stop_id')->references('id')->on('stops');

            $table->unsignedInteger('end_stop_id');
            $table->foreign('end_stop_id')->references('id')->on('stops');

            $table->double('end_point_lat');
            $table->double('end_point_lng');

            $table->text('start_address');
            $table->text('destination_address');

            //planned_start_time
            $table->time('planned_start_time');

            $table->double('trip_price');
            $table->double('paid_price')->default(0.0);

            $table->double('driver_share')->default(0.0);
            $table->double('admin_share')->default(0.0);

            //ride status
            $table->unsignedInteger('ride_status')->default(0);
            //0 not ride, 1-ride, 2-miss ride, 3-drop off, 4 - cancelled by admin

            // $table->unsignedInteger('payment_method')->nullable()->default(null); //, 0-wallet, 1-card, 2-paypal

            // $table->string('card_brand')->nullable();
            // $table->string('card_last_four', 4)->nullable();
            // $table->string('exp_month', 2)->nullable();
            // $table->string('exp_year', 4)->nullable();

            // $table->string('paypal_email')->nullable();

            // $table->unsignedInteger('status_id');
            // $table->foreign('status_id')->references('id')->on('statuses');

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
        Schema::dropIfExists('customer_reserved_trips');
    }
}
