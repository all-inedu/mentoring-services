<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProgDtlIdToStudentActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('prog_dtl_id')->nullable();
            $table->foreign('prog_dtl_id')->references('id')->on('programme_details')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_activities', function (Blueprint $table) {
            $table->dropColumn('prog_dtl_id');
            $table->dropForeign('student_activities_prog_dtl_id_foreign');
        });
    }
}
