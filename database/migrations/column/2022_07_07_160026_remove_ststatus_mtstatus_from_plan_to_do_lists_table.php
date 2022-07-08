<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveStstatusMtstatusFromPlanToDoListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plan_to_do_lists', function (Blueprint $table) {
            $table->dropColumn('st_status');
            $table->dropColumn('mt_status');
            $table->tinyInteger('status')->after('due_date')->default(0)->comment('0: waiting, 1: need confirmation, 2: need revise, 3: finished');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plan_to_do_lists', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
