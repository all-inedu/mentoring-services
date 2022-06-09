<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMtConfirmStatusLocationPwCallStatusToStudentActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('student_activities', function (Blueprint $table) {
            $table->string('mt_confirm_status', 20)->nullable()->after('std_act_status');
            $table->string('location_pw', 20)->nullable()->after('location_link');
            $table->string('call_status', 10)->nullable()->after('call_date');
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
            $table->dropColumn('mt_confirm_status');
            $table->dropColumn('location_pw');
            $table->dropColumn('call_status');
        });
    }
}
