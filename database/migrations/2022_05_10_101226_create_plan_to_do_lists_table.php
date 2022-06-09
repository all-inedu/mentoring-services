<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanToDoListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_to_do_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_mentors_id');
            $table->foreign('student_mentors_id')->references('id')->on('student_mentors')->onUpdate('cascade')->onDelete('cascade');

            $table->string('task_name');
            $table->text('description');
            $table->dateTime('due_date');
            $table->string('content')->comment('personal brand, career exploration, etc');
            $table->string('status', 20);
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
        Schema::dropIfExists('plan_to_do_lists');
    }
}
