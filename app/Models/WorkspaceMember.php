<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** A user's place in a workspace, with their role. */
#[Fillable(['workspace_id', 'user_id', 'role'])]
class WorkspaceMember extends Pivot
{
    use HasUuids;

    protected $table = 'workspace_members';

    public $incrementing = false;

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => WorkspaceRole::class,
        ];
    }
}
