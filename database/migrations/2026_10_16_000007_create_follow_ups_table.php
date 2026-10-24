<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Things the agent means to do for a lead or a client by a due time. A lead's follow-ups
     * also get the client once it's promoted; a client added directly has no lead.
     * Follow-ups created for a policy (a renewal or a premium due) point to it; the reminder
     * key, such as "renewal:2027-08-01", keeps the same reminder from being created twice.
     */
    public function up(): void
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reminder_key', 60)->nullable();
            $table->string('type', 30);
            $table->string('title');
            $table->text('notes')->nullable();
            $table->dateTime('due_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'completed_at', 'due_at']);
            $table->index(['lead_id', 'completed_at']);
            $table->index(['client_id', 'completed_at']);
            $table->unique(['policy_id', 'reminder_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
