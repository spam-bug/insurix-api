<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Follow-ups created for a policy (a renewal or a premium due) point to it. The reminder
     * key, such as "renewal:2027-08-01", keeps the same reminder from being created twice.
     */
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->foreignId('policy_id')->nullable()->after('lead_id')->constrained()->nullOnDelete();
            $table->string('reminder_key', 60)->nullable()->after('policy_id');

            $table->unique(['policy_id', 'reminder_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropUnique(['policy_id', 'reminder_key']);
            $table->dropConstrainedForeignId('policy_id');
            $table->dropColumn('reminder_key');
        });
    }
};
