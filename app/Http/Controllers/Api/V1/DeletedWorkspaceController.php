<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\ListDeletedWorkspaces;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DeletedWorkspaceResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeletedWorkspaceController extends Controller
{
    public function index(#[CurrentUser] User $user, ListDeletedWorkspaces $listDeletedWorkspaces): AnonymousResourceCollection
    {
        return DeletedWorkspaceResource::collection($listDeletedWorkspaces->handle($user));
    }
}
