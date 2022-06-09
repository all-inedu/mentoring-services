<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMeetingLinkToGroupMeetingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_meetings', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('meeting_date');
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
            $table->dropColumn('meeting_link');
        });
    }
}
