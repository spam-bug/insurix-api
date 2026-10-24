<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Policy;
use Carbon\CarbonImmutable;

class RenewalComingUpNotification extends WorkspaceNotification
{
    public function __construct(public Policy $policy, public CarbonImmutable $renewsOn)
    {
        parent::__construct($policy->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::RenewalComingUp;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Renewal coming up',
            'body' => "{$this->policy->client->name}’s {$this->policy->product_name} renews {$this->renewsOn->format('M j')}.",
            'actor' => null,
            'subject' => ['kind' => 'policy', 'id' => $this->policy->id, 'name' => $this->policy->product_name],
        ];
    }
}
