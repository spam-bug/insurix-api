<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One spreadsheet row of an import: what the file said, the cleaned values, any problems,
     * and the existing record it matches when it's a duplicate.
     */
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status', 20);
            $table->json('values');
            $table->json('data');
            $table->json('errors')->nullable();
            $table->uuid('duplicate_id')->nullable();
            $table->string('duplicate_name')->nullable();
            $table->unsignedInteger('same_person_as')->nullable();
            $table->timestamps();

            $table->index(['import_id', 'status', 'row_number']);
            $table->index(['import_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
