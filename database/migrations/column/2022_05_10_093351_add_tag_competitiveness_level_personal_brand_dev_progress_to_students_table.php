<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTagCompetitivenessLevelPersonalBrandDevProgressToStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('tag')->nullable();
            $table->string('competitiveness_level')->nullable();
            $table->string('personal_brand_dev_progress')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('tag');
            $table->dropColumn('competitiveness_level');
            $table->dropColumn('personal_brand_dev_progress');
        });
    }
}
