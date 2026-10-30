<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Premium given away without payment. A link carries a duration (no days means forever) and
     * can be claimed a set number of times, optionally only by one email. Every grant, by link or
     * given directly, is kept as history; the workspace holds the resulting plan and end date.
     */
    public function up(): void
    {
        Schema::create('premium_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 64)->unique();
            $table->unsignedInteger('days')->nullable();
            $table->unsignedInteger('max_claims')->default(1);
            $table->string('email')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_by');
        });

        Schema::create('premium_grants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('premium_link_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('days')->nullable();
            $table->timestamp('plan_ends_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['premium_link_id', 'workspace_id']);
            $table->index('workspace_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('premium_grants');
        Schema::dropIfExists('premium_links');
    }
};
