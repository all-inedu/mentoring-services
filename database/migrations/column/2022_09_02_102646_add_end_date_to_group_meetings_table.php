<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEndDateToGroupMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_meetings', function (Blueprint $table) {
            $table->dateTime('end_meeting_date')->after('meeting_date')->nullable();
            $table->renameColumn('meeting_date', 'start_meeting_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_meetings', function (Blueprint $table) {
            //
        });
    }
}
