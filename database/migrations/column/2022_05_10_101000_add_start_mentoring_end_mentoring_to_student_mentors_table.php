<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartMentoringEndMentoringToStudentMentorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_mentors', function (Blueprint $table) {
            $table->date('start_mentoring')->nullable();
            $table->date('end_mentoring')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_mentors', function (Blueprint $table) {
            $table->dropColumn('start_mentoring');
            $table->dropColumn('end_mentoring');
        });
    }
}
