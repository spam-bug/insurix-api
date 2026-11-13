<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A premium the client paid, and the commission it earns the agent: the estimate from the
     * agent's rate, then the amount their statement actually shows.
     */
    public function up(): void
    {
        Schema::create('policy_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_expected', 12, 2)->default(0);
            $table->decimal('commission_received', 12, 2)->nullable();
            $table->date('commission_received_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'due_on']);
            $table->index(['workspace_id', 'commission_received_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_payments');
    }
};
