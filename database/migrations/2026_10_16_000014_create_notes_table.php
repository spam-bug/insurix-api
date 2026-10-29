<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What agents write down: about a lead, a client or a policy, or about nothing in particular.
     * A note keeps every person it concerns (a policy note also has the client and their lead),
     * like follow-ups. A note on a lead has its entry in the lead's timeline.
     */
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('policy_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_activity_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index('user_id');
            $table->index('lead_id');
            $table->index('client_id');
            $table->index('policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
