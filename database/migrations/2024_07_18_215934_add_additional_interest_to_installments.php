<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalInterestToInstallments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->float('additional_interest_value', 20, 10)->default(0)->comment("Valor del porcentaje adicional");
            $table->float('additional_interest_paid', 20, 10)->default(0)->comment("Valor del porcentaje adicional pagado");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('installments', function (Blueprint $table) {
            //
        });
    }
}
