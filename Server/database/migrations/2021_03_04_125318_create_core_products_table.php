<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('core_products', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type');
            $table->text('features');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('core_products');
        Schema::table('flights', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
