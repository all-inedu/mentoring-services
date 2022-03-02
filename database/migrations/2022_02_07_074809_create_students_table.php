<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birthday')->nullable();
            $table->bigInteger('phone_number')->nullable();
            $table->integer('grade')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->bigInteger('total_exp')->default('0');
            
            $table->text('image')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('password')->nullable();

            $table->string('imported_from')->nullable();
            $table->string('imported_id')->nullable();
            
            $table->boolean('status')->default(true);
            $table->boolean('is_verified')->default(false);

            $table->rememberToken();
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
        Schema::dropIfExists('students');
    }
}
