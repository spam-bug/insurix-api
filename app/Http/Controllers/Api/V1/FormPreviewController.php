<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\CreateFormPreview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\ManageFormRequest;
use App\Models\Form;
use Illuminate\Http\JsonResponse;

class FormPreviewController extends Controller
{
    public function store(ManageFormRequest $request, Form $form, CreateFormPreview $createFormPreview): JsonResponse
    {
        return response()->json(['data' => ['token' => $createFormPreview->handle($form)]], 201);
    }
}
