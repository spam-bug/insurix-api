<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Who a note mentions. The note is any kind of note (a lead note today, the Notes module
     * later), so "notes that mention me" stays one query whatever the note lives on.
     */
    public function up(): void
    {
        Schema::create('mentions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->uuidMorphs('mentionable');
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('mentioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mentionable_type', 'mentionable_id', 'user_id']);
            $table->index(['user_id', 'workspace_id']);
            $table->index('mentioned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
