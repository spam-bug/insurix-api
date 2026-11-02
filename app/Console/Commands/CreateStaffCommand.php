<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Staff\CreateStaff;
use App\DataTransferObject\Staff\StaffData;
use App\Enums\StaffRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('staff:create
    {email : Their work email, used to sign in to the staff console}
    {--name= : Their name; asked for when left out}
    {--role=admin : admin or support}')]
#[Description('Add someone to the Suvvi team, with access to the staff console')]
class CreateStaffCommand extends Command
{
    public function handle(CreateStaff $createStaff): int
    {
        $input = [
            'name' => $this->option('name') ?? $this->ask('Name'),
            'email' => $this->argument('email'),
            'password' => $this->secret('Password (at least 12 characters)'),
            'role' => $this->option('role'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('staff', 'email')],
            'password' => ['required', Password::min(12)],
            'role' => ['required', Rule::enum(StaffRole::class)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $staff = $createStaff->handle(StaffData::fromArray([...$validator->validated(), 'role' => StaffRole::from($input['role'])]));

        $this->info("Added {$staff->name} ({$staff->role->label()}). They set up two-factor the first time they sign in.");

        return self::SUCCESS;
    }
}
