<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CommentStatusToUniShortlistedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uni_shortlisteds', function (Blueprint $table) {
            $table->integer('status')->default(0)->change()->comment('0: waitlisted, 1: accepted, 2: applied, 3: rejected');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uni_shortlisteds', function (Blueprint $table) {
            //
        });
    }
}
