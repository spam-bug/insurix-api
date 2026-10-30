<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Forms a workspace shares to collect leads. The builder edits the draft; publishing copies it
     * to `published`, which is what visitors see. Both hold the whole document: sections with
     * their fields, and the form's settings. One form per workspace is the default, opened by the
     * share button. Each response keeps the fields it was answered with, so editing the form never
     * changes how old answers read.
     */
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('status', 20);
            $table->boolean('is_default')->default(false);
            $table->string('template', 50)->nullable();
            $table->json('draft');
            $table->json('published')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'is_default']);
        });

        Schema::create('form_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('form_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20);
            $table->json('answers');
            $table->json('fields');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
            $table->index(['workspace_id', 'status']);
            $table->index('lead_id');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_responses');
        Schema::dropIfExists('forms');
    }
};
