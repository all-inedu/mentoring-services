<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watch_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('std_act_id');
            $table->foreign('std_act_id')->references('id')->on('student_activities')->onUpdate('cascade')->onDelete('cascade');
            $table->time('video_duration')->nullable();
            $table->time('current_time')->nullable();
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
        Schema::dropIfExists('watch_details');
    }
}
