<?php

declare(strict_types=1);

use App\Enums\ExportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A zip of the workspace's records as CSV files, built in the background for the owner and
     * kept a few days for download.
     */
    public function up(): void
    {
        Schema::create('workspace_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default(ExportStatus::Processing->value);
            $table->string('path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at']);
            $table->index('requested_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_exports');
    }
};
