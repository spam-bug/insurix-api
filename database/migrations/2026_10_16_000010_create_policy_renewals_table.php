<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The outcome the agent recorded for each of a policy's renewal dates.
     */
    public function up(): void
    {
        Schema::create('policy_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->string('outcome', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'due_on']);
            $table->index(['workspace_id', 'due_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_renewals');
    }
};
