<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniRequirementMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    protected $timestamp = false;

    public function up()
    {
        Schema::create('uni_requirement_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('med_id');
            $table->foreign('med_id')->references('id')->on('medias')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('uni_req_id');
            $table->foreign('uni_req_id')->references('id')->on('uni_requirements')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::dropIfExists('uni_requirement_media');
    }
}
