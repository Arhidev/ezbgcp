<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id');

            $table->string('account_number');
            $table->string('beneficiary_name');
            $table->string('beneficiary_address');
            $table->string('bank_name');

            $table->string('routing_number')->nullable()->default(null);
            $table->string('iban')->nullable()->default(null);
            $table->string('swift')->nullable()->default(null);
            $table->string('bic')->nullable()->default(null);

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
        Schema::dropIfExists('bank_accounts');
    }
}