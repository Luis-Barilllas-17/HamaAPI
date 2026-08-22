<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hamas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('ancho');
            $table->unsignedTinyInteger('alto');
            $table->unsignedInteger('cantidad_cuentas');
            $table->string('color_principal', 7);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hamas');
    }
};
