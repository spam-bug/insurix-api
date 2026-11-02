<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Actions\Premium\GrantPremium;
use App\DataTransferObject\Premium\PremiumGrantData;
use App\Enums\StaffActivityType;
use App\Models\PremiumGrant;
use App\Models\Staff;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class GivePremium
{
    public function __construct(
        private GrantPremium $grantPremium,
        private RecordStaffActivity $recordStaffActivity,
    ) {}

    public function handle(Workspace $workspace, PremiumGrantData $data, Staff $staff): PremiumGrant
    {
        return DB::transaction(function () use ($workspace, $data, $staff) {
            $grant = $this->grantPremium->handle($workspace, $data);

            $this->recordStaffActivity->handle($staff, StaffActivityType::PremiumGranted, $workspace, [
                'days' => $data->days,
                'note' => $data->note,
                'plan_ends_at' => $grant->plan_ends_at?->toIso8601String(),
            ]);

            return $grant;
        });
    }
}
