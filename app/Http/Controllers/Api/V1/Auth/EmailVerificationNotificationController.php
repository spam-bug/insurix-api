<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\SendEmailVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\SendEmailVerificationRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class EmailVerificationNotificationController extends Controller
{
    public function store(SendEmailVerificationRequest $request, #[CurrentUser] User $user, SendEmailVerification $sendEmailVerification): Response
    {
        $sendEmailVerification->handle($user);

        return response()->noContent();
    }
}
