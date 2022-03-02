<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgrammeSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programme_schedules', function (Blueprint $table) {
            $table->id();
            //! TODO - add foreign key to 'roles'
            $table->unsignedBigInteger('prog_id');
            $table->foreign('prog_id')->references('id')->on('programmes')->onUpdate('cascade')->onDelete('cascade');
            $table->date('prog_sch_start_date');
            $table->time('prog_sch_start_time');
            $table->date('prog_sch_end_date')->nullable();
            $table->time('prog_sch_end_time');
            $table->string('status')->default('active');
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
        Schema::dropIfExists('programme_schedules');
    }
}
