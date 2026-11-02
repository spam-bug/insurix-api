<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Actions\Premium\RevokePremium;
use App\Enums\StaffActivityType;
use App\Models\Staff;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class EndPremium
{
    public function __construct(
        private RevokePremium $revokePremium,
        private RecordStaffActivity $recordStaffActivity,
    ) {}

    public function handle(Workspace $workspace, Staff $staff): void
    {
        DB::transaction(function () use ($workspace, $staff) {
            $this->revokePremium->handle($workspace);
            $this->recordStaffActivity->handle($staff, StaffActivityType::PremiumEnded, $workspace);
        });
    }
}
