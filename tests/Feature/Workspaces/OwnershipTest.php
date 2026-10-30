<?php

declare(strict_types=1);

use App\Enums\WorkspaceRole;
use App\Models\User;

/** An owner with one member in their workspace. */
function ownerWithMember(): array
{
    $owner = User::factory()->create();
    $member = User::factory()->create();
    workspaceOf($owner)->users()->attach($member, ['role' => WorkspaceRole::Member->value]);

    return [$owner, $member, workspaceOf($owner)->members()->where('user_id', $member->id)->sole()];
}

it('hands the workspace to a member, keeping the old owner as a member', function () {
    [$owner, $member, $membership] = ownerWithMember();
    $workspace = workspaceOf($owner);

    $this->actingAs($owner)->postJson(ws($owner).'/ownership', ['member_id' => $membership->id])->assertNoContent();

    expect($workspace->members()->where('user_id', $member->id)->value('role'))->toBe(WorkspaceRole::Owner)
        ->and($workspace->members()->where('user_id', $owner->id)->value('role'))->toBe(WorkspaceRole::Member);
    $this->putJson("/api/v1/workspaces/{$workspace->id}", ['name' => 'Mine'])->assertForbidden();
});

it('only lets the owner hand over, and only to a member of this workspace', function () {
    [$owner, $member, $membership] = ownerWithMember();
    $outsider = User::factory()->create();

    $this->actingAs($member)->postJson(ws($owner).'/ownership', ['member_id' => $membership->id])->assertForbidden();
    $this->actingAs($owner)->postJson(ws($owner).'/ownership', ['member_id' => workspaceOf($outsider)->members()->sole()->id])
        ->assertUnprocessable()->assertJsonValidationErrors('member_id');
    $this->postJson(ws($owner).'/ownership', ['member_id' => workspaceOf($owner)->members()->where('user_id', $owner->id)->sole()->id])
        ->assertUnprocessable()->assertJsonValidationErrors('member_id');
});
