<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Policies, recorded by hand from the insurer's documents (there's no insurer integration):
     * enough to track applications, renewals, payments and commission. A policy can point to a
     * catalog product; its product name stays as recorded, so deleted products still read
     * correctly. A pending one has an application stage and when it last moved.
     */
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('policy_number', 100)->nullable();
            $table->string('type', 30);
            $table->string('status', 30)->default(PolicyStatus::Pending->value);
            $table->string('application_stage', 30)->nullable();
            $table->timestamp('stage_changed_at')->nullable();
            $table->decimal('premium', 12, 2);
            $table->decimal('coverage_amount', 14, 2)->nullable();
            $table->string('payment_frequency', 30);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('annual_premium', 14, 2)->default(0);
            $table->decimal('commission', 14, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->string('renewal_term', 20)->nullable();
            $table->date('renewal_date')->nullable();
            $table->date('commission_received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'renewal_date']);
            $table->index(['workspace_id', 'commission_received_at']);
            $table->index('client_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
