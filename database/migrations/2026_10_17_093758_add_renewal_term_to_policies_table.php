<?php

use App\Enums\RenewalTerm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Policies renew monthly, quarterly, annually or on a custom date. renewal_date now holds
     * only the custom date; set terms are worked out from the effective date. Policies that
     * already have a renewal date keep it as a custom date.
     */
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->string('renewal_term', 20)->nullable()->after('effective_date');
        });

        DB::table('policies')->whereNotNull('renewal_date')->update(['renewal_term' => RenewalTerm::Custom->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn('renewal_term');
        });
    }
};
