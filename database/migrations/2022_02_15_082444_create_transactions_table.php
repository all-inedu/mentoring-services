<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('st_act_id');
            $table->foreign('st_act_id')->references('id')->on('student_activities')->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedBigInteger('promo_id')->nullable();
            $table->foreign('promo_id')->references('id')->on('promotions')->onUpdate('cascade')->onDelete('cascade');

            $table->bigInteger('amount');
            $table->bigInteger('total_amount');
            $table->string('status')->default('waiting');
            $table->text('payment_proof')->nullable();
            $table->string('payment_method')->nullable();
            $table->datetime('payment_date')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
