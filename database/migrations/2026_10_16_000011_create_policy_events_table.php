<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A policy's timeline: recorded, stage changes, approved or declined, requirements added
     * or received, and renewal outcomes.
     */
    public function up(): void
    {
        Schema::create('policy_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_events');
    }
};
