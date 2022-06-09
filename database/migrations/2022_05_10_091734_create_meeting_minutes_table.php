<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingMinutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('st_act_id');
            $table->foreign('st_act_id')->references('id')->on('student_activities')->onUpdate('cascade')->onDelete('cascade');
            $table->longText('academic_performance')->nullable();
            $table->longText('exploration')->nullable();
            $table->longText('writing_skills')->nullable();
            $table->longText('personal_brand')->nullable();
            $table->text('mt_todos_note');
            $table->text('st_todos_note');
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
        Schema::dropIfExists('meeting_minutes');
    }
}
