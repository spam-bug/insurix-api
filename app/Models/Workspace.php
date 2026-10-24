<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WorkspaceType;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;

/** Owns every lead, client, policy and the rest; its type comes from the template it was made from. */
#[Fillable(['name', 'type', 'timezone', 'bio'])]
#[Hidden(['lead_form_token'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory, HasUuids;

    /**
     * Agents are in the Philippines for now; the timezone sets where the workspace's day starts.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'Asia/Manila',
    ];

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk(User::UPLOADS_DISK)->url($this->logo_path) : null;
    }

    /**
     * @return BelongsToMany<User, $this, WorkspaceMember>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->using(WorkspaceMember::class)
            ->as('membership')
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<WorkspaceMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * @return HasMany<WorkspaceInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Policy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(Policy::class);
    }

    /**
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * Bell notifications about this workspace, for any of its members.
     *
     * @return HasMany<DatabaseNotification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(DatabaseNotification::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WorkspaceType::class,
        ];
    }
}
