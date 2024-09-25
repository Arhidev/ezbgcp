<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRedemptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('redemptions', function (Blueprint $table) {
            $table->increments('id');

            $table->double('redemption_amount');

            
            $table->unsignedInteger('redemption_type_id')->default(1);
            $table->foreign('redemption_type_id')->references('id')->on('redemption_types');

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedInteger('bank_account_id')->nullable();
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');

            $table->unsignedInteger('paypal_account_id')->nullable();
            $table->foreign('paypal_account_id')->references('id')->on('paypal_accounts');

            $table->unsignedInteger('mobile_money_account_id')->nullable();
            $table->foreign('mobile_money_account_id')->references('id')->on('mobile_money_accounts');

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
        Schema::dropIfExists('redemptions');
    }
}
