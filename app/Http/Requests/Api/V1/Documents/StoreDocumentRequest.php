<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Documents;

use App\Actions\Documents\MeasureStorage;
use App\Enums\DocumentCategory;
use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use App\Models\Workspace;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * An upload for a client: the file plus its details. Without a name, the file's name is used.
 */
class StoreDocumentRequest extends FormRequest
{
    /** Largest file accepted, in kilobytes (10 MB). */
    public const MAX_KILOBYTES = 10240;

    /** Uploads come from a client's page (the client is in the route) or the Documents page. */
    public function authorize(): bool
    {
        return $this->route('client')
            ? $this->user()->can('update', $this->route('client'))
            : $this->user()->can('create', Document::class);
    }

    /**
     * From the Documents page, the client is optional: without one the file waits in the inbox,
     * and it can't be linked to a policy. Files that would go over the storage limit are refused.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = app(Workspace::class);
        $clientId = $this->route('client')?->id ?? $this->input('client_id');

        return [
            'file' => [
                'required', 'file', 'max:'.self::MAX_KILOBYTES, 'mimes:pdf,jpg,jpeg,png,webp,heic,doc,docx,xls,xlsx',
                function (string $attribute, mixed $file, Closure $fail) use ($workspace) {
                    $storage = app(MeasureStorage::class)->handle($workspace);
                    if ($file instanceof UploadedFile && $storage['limit'] < $storage['used'] + $file->getSize()) {
                        $fail('Your workspace’s storage is full. Delete files you no longer need to make room.');
                    }
                },
            ],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'client_id' => ['sometimes', 'nullable', 'uuid', Rule::exists(Client::class, 'id')->where('workspace_id', $workspace->id)],
            'policy_id' => ['nullable', 'uuid', Rule::prohibitedIf($clientId === null), Rule::exists(Policy::class, 'id')->where('client_id', $clientId)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
