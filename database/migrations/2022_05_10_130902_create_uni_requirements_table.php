<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUniRequirementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uni_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uni_id');
            $table->foreign('uni_id')->references('id')->on('uni_shortlisteds')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedBigInteger('med_cat_id');
            $table->foreign('med_cat_id')->references('id')->on('media_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->text('description');
            $table->integer('toefl_score');
            $table->integer('ielts_score');
            $table->string('essay_title');
            $table->text('publication_links');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('uni_requirements');
    }
}
