<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\StaffActivityType;
use App\Models\Staff;
use App\Models\StaffActivity;
use Illuminate\Database\Eloquent\Model;

class RecordStaffActivity
{
    /**
     * Writes the audit log entry. Call it inside the change's transaction, so a change that
     * rolls back leaves no entry, and an entry never goes missing for a change that happened.
     *
     * @param  array<string, mixed>  $properties
     */
    public function handle(Staff $staff, StaffActivityType $type, Model $subject, array $properties = []): StaffActivity
    {
        return StaffActivity::create([
            'staff_id' => $staff->id,
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'properties' => $properties ?: null,
        ]);
    }
}
