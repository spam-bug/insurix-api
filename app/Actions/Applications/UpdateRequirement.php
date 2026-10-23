<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Applications\RequirementData;
use App\Enums\PolicyEventType;
use App\Models\ApplicationRequirement;
use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateRequirement
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Ticks a requirement off as received today (or un-ticks it), and links the document that
     * covers it. Linking a document counts as receiving it.
     */
    public function handle(ApplicationRequirement $requirement, RequirementData $data, User $actor): ApplicationRequirement
    {
        $document = $data->documentId === null ? null : Document::findOrFail($data->documentId);
        $received = $data->received || $document !== null;
        $wasReceived = $requirement->received_on !== null;

        $requirement = DB::transaction(function () use ($requirement, $received, $wasReceived, $document, $actor) {
            $requirement->update([
                'received_on' => $received ? ($requirement->received_on ?? CarbonImmutable::now($actor->timezone)->toDateString()) : null,
                'document_id' => $document?->id,
            ]);

            if ($received && ! $wasReceived) {
                $this->recordPolicyEvent->handle($requirement->policy, PolicyEventType::RequirementReceived, $actor, ['title' => $requirement->title]);
            }

            return $requirement;
        });

        Cache::tags("agent:{$requirement->policy->user_id}")->flush();

        return $requirement;
    }
}
