<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            $table->string('uid');
            $table->string('fcm_token')->nullable()->default(null);

            $table->string('avatar')->default('avatar.png');

            $table->string('tel_number')->nullable();
            //address
            $table->string('address')->nullable();
            
            $table->string('license_url')->nullable();
            $table->double('wallet')->default(0.0);

            $table->unsignedInteger('status_id')->default(1); //1, active, 2 pending, 3 suspended
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');

            $table->unsignedInteger('role')->default(1); //0, admin, 1 customer, 2 driver

            //preferences for redemptions
            $table->unsignedInteger('redemption_preference')->default(1); //1, cash, 2 bank, 3 paypal, 4 mobile money

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
