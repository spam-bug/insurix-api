<?php

use App\Enums\PolicyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Policies are recorded by hand from the insurer's documents: enough to track renewals,
     * payments and commission, not a copy of the insurer's system.
     */
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('insurer');
            $table->string('product_name');
            $table->string('policy_number', 100)->nullable();
            $table->string('type', 30);
            $table->string('status', 30)->default(PolicyStatus::Pending->value);
            $table->decimal('premium', 12, 2);
            $table->string('payment_frequency', 30);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->date('commission_received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'renewal_date']);
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
