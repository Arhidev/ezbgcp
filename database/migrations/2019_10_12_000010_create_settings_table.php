<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');

            $table->double('rate_per_km')->default(10.0);
            $table->double('commission')->default(10.0);
            $table->integer('publish_trips_future_days')->default(3);
            //max distance from stop to search
            $table->double('max_distance_to_stop')->default(10.0);
            //distance to stop to mark as driver arrived
            $table->double('distance_to_stop_to_mark_arrived')->default(100);
            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');

            //allow ads in driver app
            $table->boolean('allow_ads_in_driver_app')->default(false);
            //allow ads in customer app
            $table->boolean('allow_ads_in_customer_app')->default(false);

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
        Schema::dropIfExists('settings');
    }
}
