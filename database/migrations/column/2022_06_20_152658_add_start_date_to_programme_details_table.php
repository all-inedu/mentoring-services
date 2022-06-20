<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartDateToProgrammeDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programme_details', function (Blueprint $table) {
            $table->dateTime('dtl_date')->after('dtl_video_link');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programme_details', function (Blueprint $table) {
            $table->dropColumn('dtl_date');
        });
    }
}
