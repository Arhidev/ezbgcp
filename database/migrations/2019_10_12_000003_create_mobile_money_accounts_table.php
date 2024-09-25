<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileMoneyAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_money_accounts', function (Blueprint $table) {
            $table->increments('id');

            $table->string('phone_number');
            $table->string('network');
            $table->string('name')->nullable();
            
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');


            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_money_accounts');
    }
}