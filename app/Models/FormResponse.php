<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FormResponseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One visitor's answers, keyed by field id, with the fields as they were when answered. */
#[Fillable(['form_id', 'workspace_id', 'lead_id', 'client_id', 'status', 'answers', 'fields', 'meta'])]
class FormResponse extends Model
{
    use HasUuids;

    /** Who answered, from the linked name questions; null when the form doesn't ask. */
    public function personName(): ?string
    {
        $answers = [];

        foreach ($this->fields as $field) {
            $answer = $this->answers[$field['id']] ?? null;

            if (($field['link'] ?? null) !== null && is_string($answer) && trim($answer) !== '') {
                $answers[$field['link']] = trim($answer);
            }
        }

        return $answers['name'] ?? (trim(($answers['first_name'] ?? '').' '.($answers['last_name'] ?? '')) ?: null);
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FormResponseStatus::class,
            'answers' => 'array',
            'fields' => 'array',
            'meta' => 'array',
        ];
    }
}
