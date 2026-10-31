<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ServiceRequestStatus;
use App\Enums\ServiceRequestView;
use App\Models\ServiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<ServiceRequest>
 */
class ServiceRequestBuilder extends Builder
{
    public function unresolved(): static
    {
        return $this->whereIn('status', ServiceRequestStatus::unresolved());
    }

    /** Unresolved and due before today, today being the workspace's date. */
    public function overdue(CarbonImmutable $today): static
    {
        return $this->unresolved()->whereNotNull('due_on')->whereDate('due_on', '<', $today->toDateString());
    }

    public function inView(?ServiceRequestView $view, CarbonImmutable $today): static
    {
        return match ($view) {
            null, ServiceRequestView::Unresolved => $this->unresolved(),
            ServiceRequestView::Overdue => $this->overdue($today),
            ServiceRequestView::WaitingOnInsurer => $this->where('status', ServiceRequestStatus::WaitingOnInsurer),
            ServiceRequestView::Resolved => $this->where('status', ServiceRequestStatus::Resolved),
        };
    }

    /** Matches the title, the insurer's reference or the client's name. */
    public function search(?string $search): static
    {
        if (blank($search)) {
            return $this;
        }

        return $this->where(fn (Builder $query) => $query
            ->whereLike('title', "%{$search}%")
            ->orWhereLike('insurer_reference', "%{$search}%")
            ->orWhereHas('client', fn (Builder $client) => $client->whereLike('name', "%{$search}%")));
    }

    /** Soonest due first (no due date last), then the oldest, so nothing waits forever. */
    public function mostUrgentFirst(): static
    {
        return $this->orderByRaw('due_on is null')->orderBy('due_on')->oldest()->oldest('id');
    }
}
