<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A policy can point to a catalog product. The product name stays on the
     * policy as recorded, so older policies and deleted products still read correctly.
     */
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->decimal('coverage_amount', 14, 2)->nullable()->after('premium');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('coverage_amount');
        });
    }
};
