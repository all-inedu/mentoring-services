<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStudentStatusToPlanToDoListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plan_to_do_lists', function (Blueprint $table) {
            $table->dropColumn('content');
            $table->dropColumn('status');
            $table->string('st_status', 20)->after('due_date')->default('in progress')->comment('in progress, finished');
            $table->string('mt_status', 20)->after('st_status')->nullable()->comment('confirmed');
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
            $table->dropColumn('st_status');
        });
    }
}
