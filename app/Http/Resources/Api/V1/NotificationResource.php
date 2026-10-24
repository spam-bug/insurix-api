<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = NotificationType::from($this->type);

        return [
            'id' => $this->id,
            'type' => ['value' => $type->value, 'label' => $type->label()],
            'title' => $this->data['title'],
            'body' => $this->data['body'],
            'actor' => $this->data['actor'],
            'subject' => $this->data['subject'],
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
