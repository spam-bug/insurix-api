<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('trigger', 30);
            $table->string('trigger_stage', 50)->nullable();
            $table->unsignedSmallInteger('idle_days')->nullable();
            $table->string('match', 10)->default('all');
            $table->json('conditions');
            $table->json('actions');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'trigger', 'enabled']);
            $table->index('user_id');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('automations');
    }
};
