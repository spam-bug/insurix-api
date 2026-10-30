<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\FormFieldType;
use App\Enums\FormTemplate;
use App\Enums\ImportField;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * What the builder offers: templates (with their starting form, for previews), field types, and the
 * lead and client details a field can be linked to, with the names agents tend to use for them so
 * the builder can suggest a link.
 */
class FormCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'templates' => array_map(fn (FormTemplate $template) => [
                'value' => $template->value,
                'label' => $template->label(),
                'description' => $template->description(),
                'document' => $template->document(),
            ], FormTemplate::cases()),
            'field_types' => array_map(fn (FormFieldType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'layout' => $type->isLayout(),
                'has_options' => $type->hasOptions(),
            ], FormFieldType::cases()),
            'links' => array_map(fn (ImportField $link) => [
                'value' => $link->value,
                'label' => $link->label(),
                'type' => $link->formFieldType()->value,
                'options' => $link->formOptions(),
                'aliases' => $link->headerNames(),
            ], ImportField::formLinks()),
        ]]);
    }
}
