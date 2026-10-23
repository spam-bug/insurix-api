<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Potential clients. Every lead starts as a prospect. The score (0 to 100, with its
     * reasons) says how likely an open lead is to become a client; it's null for clients and
     * lost leads, and stored so the list can sort by it.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('source', 50);
            $table->string('interest', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default(LeadStatus::Prospect->value);
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('score_reasons')->nullable();
            $table->timestamp('meeting_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
