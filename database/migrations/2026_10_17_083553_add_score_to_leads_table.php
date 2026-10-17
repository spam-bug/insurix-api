<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How likely an open lead is to become a client, 0 to 100, with the reasons behind it.
     * Stored so the list can sort by it; null for clients and lost leads.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedTinyInteger('score')->nullable()->after('status');
            $table->json('score_reasons')->nullable()->after('score');

            $table->index(['user_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'score']);
            $table->dropColumn(['score', 'score_reasons']);
        });
    }
};
