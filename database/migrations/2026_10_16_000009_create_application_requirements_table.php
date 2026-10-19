<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the insurer still needs before deciding on an application, ticked off as it comes
     * in, optionally with the document that covers it.
     */
    public function up(): void
    {
        Schema::create('application_requirements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('policy_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignUuid('document_id')->nullable()->constrained()->nullOnDelete();
            $table->date('received_on')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'received_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_requirements');
    }
};
