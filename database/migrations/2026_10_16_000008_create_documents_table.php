<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files the agent keeps for a client: policy contracts, application forms, IDs and other
     * requirements. A document can point to one of the client's policies. Files live on the
     * private disk and are only served through the API.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            // Null while the file sits unassigned in the Documents inbox.
            $table->foreignUuid('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('policy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 30);
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index(['client_id', 'created_at']);
            $table->index('policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
