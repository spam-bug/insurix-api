<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Client;
use App\Models\FormResponse;
use App\Models\Lead;

/**
 * Someone answered a form without becoming a new lead: a plain response (it opens the form's
 * responses), or someone already in the workspace answering again (it opens their lead or client).
 */
class FormResponseNotification extends WorkspaceNotification
{
    /**
     * @param  list<string>  $filledIn  the details the answers added to the existing lead or client
     */
    public function __construct(public FormResponse $response, public string $formName, public Client|Lead|null $person = null, public array $filledIn = [])
    {
        parent::__construct($response->workspace_id);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::FormResponse;
    }

    public function toArray(object $notifiable): array
    {
        if ($this->person === null) {
            $who = $this->response->personName() ?? 'Someone';

            return [
                'title' => "New response to {$this->formName}",
                'body' => "{$who} answered {$this->formName}.",
                'actor' => null,
                'subject' => ['kind' => 'form', 'id' => $this->response->form_id, 'name' => $this->formName],
            ];
        }

        $details = str_replace('_', ' ', implode(', ', $this->filledIn));

        return [
            'title' => "{$this->person->name} answered {$this->formName}",
            'body' => $this->filledIn === []
                ? 'They’re already in your workspace; nothing new to add.'
                : "They’re already in your workspace; added their {$details}.",
            'actor' => null,
            'subject' => ['kind' => $this->person instanceof Client ? 'client' : 'lead', 'id' => $this->person->id, 'name' => $this->person->name],
        ];
    }
}
