<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A spreadsheet an agent is bringing in, from upload through column matching, the checked
     * preview and the import itself. The file stays on private storage until the import is done.
     * Fixes are the suggested corrections the agent accepted, by row and field.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20);
            $table->string('original_name');
            $table->string('path');
            $table->json('headers');
            $table->json('sample');
            $table->json('mapping')->nullable();
            $table->json('fixes')->nullable();
            $table->boolean('update_duplicates')->default(false);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->text('failure')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
