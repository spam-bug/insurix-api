<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\ShowPublicForm;
use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;

/** A form's draft for a preview link; an unreadable or expired token is not found. */
class PublicFormPreviewController extends Controller
{
    public function show(string $token, ShowPublicForm $showPublicForm): JsonResponse
    {
        try {
            $preview = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException) {
            abort(404);
        }

        abort_if(($preview['expires'] ?? 0) < now()->timestamp, 404);
        $form = Form::findOrFail($preview['form'] ?? null);

        return response()->json(['data' => $showPublicForm->handle($form, $form->draft)]);
    }
}
