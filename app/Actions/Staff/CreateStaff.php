<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\DataTransferObject\Staff\StaffData;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

class CreateStaff
{
    public function handle(StaffData $data): Staff
    {
        return DB::transaction(fn () => Staff::create($data->toArray()));
    }
}
