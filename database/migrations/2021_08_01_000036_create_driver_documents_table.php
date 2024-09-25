<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedInteger('driver_information_id');
            $table->foreign('driver_information_id')->references('id')->on('driver_information');

            $table->string('document_name');
            $table->string('document_number');
            $table->date('expiry_date');
            $table->text('local_file_path');
            $table->text('remote_file_path')->nullable();

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
        Schema::dropIfExists('driver_documents');
    }
}