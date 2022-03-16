<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgrammesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            //! TODO - add foreign key to 'roles'
            $table->unsignedBigInteger('prog_mod_id');
            $table->foreign('prog_mod_id')->references('id')->on('programme_modules')->onUpdate('cascade')->onDelete('cascade');
            $table->string('prog_name');
            $table->text('prog_desc');
            $table->text('prog_has')->nullable();
            $table->text('prog_href')->nullable();
            $table->bigInteger('prog_price')->default(0);
            // $table->string('status');
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
        Schema::dropIfExists('programmes');
    }
}
