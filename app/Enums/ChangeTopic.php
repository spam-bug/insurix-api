<?php

declare(strict_types=1);

namespace App\Enums;

/** Which part of a workspace changed, so open tabs know what to refresh. */
enum ChangeTopic: string
{
    case Leads = 'leads';
    case Clients = 'clients';
    case Policies = 'policies';
    case FollowUps = 'follow-ups';
    case Documents = 'documents';
    case Notes = 'notes';
    case ServiceRequests = 'service-requests';
    case Automations = 'automations';
    case Forms = 'forms';
    case Products = 'products';
    case Team = 'team';
    case Workspace = 'workspace';
}
