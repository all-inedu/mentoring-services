<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('promo_title')->nullable();
            $table->text('promo_desc')->nullable();
            $table->string('promo_code');
            $table->bigInteger('discount');
            $table->datetime('promo_start_date')->nullable();
            $table->datetime('promo_end_date')->nullable();
            $table->boolean('limited');
            $table->integer('total_used');
            $table->string('status');
            $table->softDeletes();
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
        Schema::dropIfExists('promotions');
    }
}
