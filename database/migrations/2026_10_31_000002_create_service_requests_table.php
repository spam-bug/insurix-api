<?php

declare(strict_types=1);

use App\Enums\ServiceRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('policy_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('status', 30)->default(ServiceRequestStatus::Open->value);
            $table->string('title');
            $table->text('details')->nullable();
            $table->date('due_on')->nullable();
            $table->decimal('claim_amount', 12, 2)->nullable();
            $table->string('insurer_reference', 100)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'due_on']);
            $table->index('client_id');
            $table->index('policy_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
