<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgrammeDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programme_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prog_id');
            $table->foreign('prog_id')->references('id')->on('programmes')->onUpdate('cascade')->onDelete('cascade');
            $table->string('dtl_category');
            $table->string('dtl_name');
            $table->string('dtl_desc');
            $table->string('dtl_price')->default(0);
            $table->text('dtl_video_link')->nullable();
            $table->string('status')->default('active');
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
        Schema::dropIfExists('programme_details');
    }
}
