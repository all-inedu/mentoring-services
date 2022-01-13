<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->datetime('birthday')->nullable();
            $table->bigInteger('phone_number')->nullable();
            $table->string('address')->nullable();

            //! TODO - add foreign key to 'roles'
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('cascade');

            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('image');
            $table->string('provider');
            $table->string('provider_id');
            $table->string('password')->nullable();

            $table->bigInteger('total_exp')->default('0');

            $table->string('imported_from')->nullable();
            $table->string('imported_id')->nullable();
            $table->boolean('status')->default(true);

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
        Schema::dropIfExists('users');
    }
}
