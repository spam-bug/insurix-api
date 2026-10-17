<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The secret part of an agent's shareable lead form link.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('lead_form_token', 64)->nullable()->unique()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['lead_form_token']);
            $table->dropColumn('lead_form_token');
        });
    }
};
