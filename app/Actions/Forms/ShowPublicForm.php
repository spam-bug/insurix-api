<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Enums\Feature;
use App\Enums\WorkspaceRole;
use App\Models\Form;
use App\Support\FormTheme;
use Laravel\Pennant\Feature as Pennant;

class ShowPublicForm
{
    /**
     * What a visitor's page needs: the document (the published one, or the draft for a preview;
     * none once closed) with its theme trimmed to the workspace's plan, and who it's from.
     *
     * @param  array<string, mixed>|null  $document
     * @return array<string, mixed>
     */
    public function handle(Form $form, ?array $document): array
    {
        $workspace = $form->workspace;
        $owner = $workspace->users()->wherePivot('role', WorkspaceRole::Owner)->first();

        if ($document !== null) {
            $document['theme'] = FormTheme::forVisitors($document['theme'] ?? null, Pennant::for($workspace)->active(Feature::FormPremiumTheme));
        }

        return [
            'form' => ['token' => $form->token, 'status' => $form->status->value, 'document' => $document],
            'agent' => [
                'name' => $workspace->name,
                'avatar_url' => $owner?->avatarUrl(),
                'logo_url' => $workspace->logoUrl(),
            ],
        ];
    }
}
