<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('deudor_id');
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('usu_crea');
            $table->integer('cant_cuotas');
            $table->integer('cant_cuotas_pagadas')->nullable()->default(0);
            $table->integer('dia_limite')->nullable()->default(1);
            $table->boolean('deudor')->comment('Solo se confirma si tiene deudor');
            $table->tinyInteger('status')->default(0)->nullable()->default(0);
            $table->date('fecha_inicio');
            $table->float('interes', 20, 2)->default(3);
            $table->float('porcentaje_interes_anual', 20, 4)->nullable()->default(0);
            $table->float('valor_cuota', 20, 4);
            $table->float('valor_credit', 20, 4);
            $table->float('valor_abonado', 20, 4)->nullable()->default(0);
            $table->float('capital_value', 20, 4)->nullable()->default(0);
            $table->float('interest_value', 20, 4)->nullable()->default(0);

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('deudor_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('sede_id')
                ->references('id')
                ->on('sedes')
                ->onDelete('cascade');

            $table->foreign('usu_crea')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');


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
        Schema::dropIfExists('credits');
    }
}
