<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('clients', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('last_name');
      $table->tinyInteger('type_document')->default(null);
      $table->integer('document_number');
      $table->string('cell_phone1')->default(null)->nullable();
      $table->string('cell_phone2')->default(null)->nullable();
      $table->string('address')->default(null)->nullable();
      $table->string('email')->default(null)->nullable();
      $table->date('fecha_nacimiento')->default(null)->nullable();
      $table->tinyText('genero')->default(null)->nullable();
      $table->tinyInteger('activo')->default(1);
      $table->string('estado_civil')->default(null)->nullable();
      $table->string('lugar_trabajo')->default(null)->nullable();
      $table->string('cargo')->default(null)->nullable();
      $table->tinyInteger('independiente')->default(null)->nullable();
      $table->string('photo')->default(null)->nullable();
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
    Schema::dropIfExists('clients');
  }
}
