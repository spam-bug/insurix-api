<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agents sell for a single insurance company, so products and policies are known by
     * their product name alone.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'insurer', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('insurer');
            $table->unique(['user_id', 'name']);
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn('insurer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->string('insurer')->default('')->after('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('insurer')->default('')->after('user_id');
            $table->unique(['user_id', 'insurer', 'name']);
        });
    }
};
