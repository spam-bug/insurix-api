<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePassword
{
    /**
     * Sets a new password and signs the agent out everywhere else, keeping the session they
     * changed it from.
     */
    public function handle(User $agent, string $password): User
    {
        DB::transaction(function () use ($agent, $password) {
            $agent->update(['password' => $password]);

            DB::table('sessions')->where('user_id', $agent->id)->where('id', '!=', session()->getId())->delete();
        });

        return $agent;
    }
}
