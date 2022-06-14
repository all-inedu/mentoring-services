<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnFromUniRequirementMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uni_requirement_media', function (Blueprint $table) {
            $table->dropForeign(['uni_req_id']);
            $table->dropColumn('uni_req_id');

            $table->unsignedBigInteger('uni_shortlisted_id')->after('med_id');
            $table->foreign('uni_shortlisted_id')->references('id')->on('uni_shortlisteds')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uni_requirement_media', function (Blueprint $table) {
            //
        });
    }
}
