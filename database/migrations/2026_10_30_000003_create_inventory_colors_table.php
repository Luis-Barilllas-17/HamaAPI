<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('hex_color', 7);
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'hex_color']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_colors');
    }
};
