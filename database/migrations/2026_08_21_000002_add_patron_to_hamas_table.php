<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hamas', function (Blueprint $table) {
            $table->json('patron')->nullable()->after('color_principal');
        });
    }

    public function down(): void
    {
        Schema::table('hamas', function (Blueprint $table) {
            $table->dropColumn('patron');
        });
    }
};
