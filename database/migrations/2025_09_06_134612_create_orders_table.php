<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('livrare_address_1')->nullable();
            $table->string('livrare_address_2')->nullable();
            $table->string('livrare_first_name')->nullable();
            $table->string('livrare_last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('greutate', 8, 2)->nullable();
            $table->string('cif')->nullable();
            $table->string('company')->nullable();
            $table->integer('nr_colete')->default(1);
            $table->integer('status')->default(0);
            $table->string('awb')->nullable();
            $table->string('curier')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
