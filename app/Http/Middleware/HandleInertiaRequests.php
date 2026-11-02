<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Staff;
use Illuminate\Http\Request;
use Inertia\Middleware;

/** Inertia for the staff console: who's signed in, and the one-off message after an action. */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var Staff|null $staff */
        $staff = $request->user('staff');

        return [
            ...parent::share($request),
            'staff' => $staff ? [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => ['value' => $staff->role->value, 'label' => $staff->role->label()],
            ] : null,
            'flash' => fn () => $request->session()->get('status'),
        ];
    }
}
