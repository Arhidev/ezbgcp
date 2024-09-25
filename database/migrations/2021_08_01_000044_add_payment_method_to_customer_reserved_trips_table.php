<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToCustomerReservedTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_reserved_trips', function (Blueprint $table) {
            $table->integer('payment_method')->default(0); //0-wallet, 1-cash
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_reserved_trips', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
}
