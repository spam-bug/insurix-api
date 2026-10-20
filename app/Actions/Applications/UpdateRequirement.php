<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\PolicyEventType;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;

class UpdateRequirement
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Ticks a requirement off as received today (or un-ticks it), and links the document that
     * covers it. Linking a document counts as receiving it.
     */
    public function handle(ApplicationRequirement $requirement, bool $received, ?Document $document, User $actor): ApplicationRequirement
    {
        $received = $received || $document !== null;
        $wasReceived = $requirement->received_on !== null;

        $requirement->update([
            'received_on' => $received ? ($requirement->received_on ?? CarbonImmutable::now($actor->timezone)->toDateString()) : null,
            'document_id' => $document?->id,
        ]);

        if ($received && ! $wasReceived) {
            $this->recordPolicyEvent->handle($requirement->policy, PolicyEventType::RequirementReceived, $actor, ['title' => $requirement->title]);
        }

        return $requirement;
    }
}
