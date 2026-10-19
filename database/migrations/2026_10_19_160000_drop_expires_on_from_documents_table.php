<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documents don't expire: the expiry date and its reminders were dropped as not useful.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'expires_on']);
            $table->dropColumn('expires_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->date('expires_on')->nullable()->after('size');
            $table->index(['user_id', 'expires_on']);
        });
    }
};
