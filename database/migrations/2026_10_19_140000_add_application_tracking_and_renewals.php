<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Applications and renewals, tracked by hand (there's no insurer integration):
     *  - a pending policy's application stage and when it last moved
     *  - the requirements the insurer still needs, ticked off as they come in
     *  - the outcome the agent records for each renewal date
     *  - a timeline of what happened to the policy
     */
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->string('application_stage', 30)->nullable()->after('status');
            $table->timestamp('stage_changed_at')->nullable()->after('application_stage');
        });

        // Applications already recorded start as submitted, counting from when they were added.
        DB::table('policies')->where('status', 'pending')->update([
            'application_stage' => 'submitted',
            'stage_changed_at' => DB::raw('created_at'),
        ]);

        Schema::create('application_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->date('received_on')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'received_on']);
        });

        Schema::create('policy_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('due_on');
            $table->string('outcome', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['policy_id', 'due_on']);
            $table->index(['user_id', 'due_on']);
        });

        Schema::create('policy_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['policy_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_events');
        Schema::dropIfExists('policy_renewals');
        Schema::dropIfExists('application_requirements');

        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn(['application_stage', 'stage_changed_at']);
        });
    }
};
