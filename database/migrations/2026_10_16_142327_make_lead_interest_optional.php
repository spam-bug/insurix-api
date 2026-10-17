<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Interest is now learned during a meeting, so a new lead may not have one yet.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('interest', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('interest', 50)->nullable(false)->change();
        });
    }
};
