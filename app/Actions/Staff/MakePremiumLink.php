<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Actions\Premium\CreatePremiumLink;
use App\DataTransferObject\Premium\PremiumLinkData;
use App\Enums\StaffActivityType;
use App\Models\PremiumLink;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class MakePremiumLink
{
    public function __construct(
        private CreatePremiumLink $createPremiumLink,
        private RecordStaffActivity $recordStaffActivity,
    ) {}

    public function handle(PremiumLinkData $data, Staff $staff): PremiumLink
    {
        return DB::transaction(function () use ($data, $staff) {
            $link = $this->createPremiumLink->handle($data);
            $this->recordStaffActivity->handle($staff, StaffActivityType::PremiumLinkCreated, $link, ['note' => $data->note]);

            return $link;
        });
    }
}
