<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->increments('id');

            //code
            $table->string('code')->unique();

            //discount
            $table->unsignedInteger('discount'); //percentage

            //limit
            $table->unsignedInteger('limit')->default(0); //0:unlimited

            //max amount
            $table->unsignedInteger('max_amount')->default(0); //0:unlimited

            //status
            $table->unsignedInteger('status')->default(0); //0:active, 1:inactive

            //expiration date
            $table->dateTime('expiration_date');

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
        Schema::dropIfExists('coupons');
    }
}
