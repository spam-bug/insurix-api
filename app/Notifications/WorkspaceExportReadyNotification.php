<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\WorkspaceExport;

class WorkspaceExportReadyNotification extends WorkspaceNotification
{
    public function __construct(public WorkspaceExport $export)
    {
        parent::__construct($export->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ExportReady;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Your workspace export is ready',
            'body' => 'Download it within '.WorkspaceExport::KEEP_DAYS.' days. It holds clients’ personal details, so keep it somewhere safe.',
            'actor' => null,
            'subject' => ['kind' => 'export', 'id' => $this->export->id, 'name' => 'Workspace export'],
        ];
    }
}
