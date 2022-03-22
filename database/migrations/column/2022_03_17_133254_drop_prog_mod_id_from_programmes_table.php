<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropProgModIdFromProgrammesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->dropForeign('programmes_prog_mod_id_foreign');
            $table->dropColumn('prog_mod_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->unsignedBigInteger('prog_mod_id');
            $table->foreign('prog_mod_id')->references('id')->on('programme_modules')->onUpdate('cascade')->onDelete('cascade');
        });
    }
}
