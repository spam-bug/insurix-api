<?php

declare(strict_types=1);

use App\Enums\Plan;
use App\Enums\StaffActivityType;
use App\Models\PremiumLink;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function staffAdmin(): Staff
{
    return Staff::factory()->withTwoFactor()->create();
}

function staffSupport(): Staff
{
    return Staff::factory()->support()->withTwoFactor()->create();
}

it('finds workspaces by name or a member’s email, by plan, and among deleted ones', function () {
    $maria = User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);
    $jose = User::factory()->create(['name' => 'Jose Cruz']);
    workspaceOf($maria)->update(['name' => 'Santos Insurance']);
    workspaceOf($jose)->forceFill(['plan' => Plan::Premium, 'plan_ends_at' => null])->save();
    $gone = workspaceOf(User::factory()->create());
    $gone->delete();

    $this->actingAs(staffSupport(), 'staff');

    $this->get('/staff/workspaces?search=maria@example')->assertInertia(fn (Assert $page) => $page
        ->component('staff/workspaces/index')
        ->has('workspaces.data', 1)
        ->where('workspaces.data.0.name', 'Santos Insurance')
        ->where('workspaces.data.0.owner.email', 'maria@example.com'));

    $this->get('/staff/workspaces?plan=premium')->assertInertia(fn (Assert $page) => $page
        ->has('workspaces.data', 1)
        ->where('workspaces.data.0.id', workspaceOf($jose)->id)
        ->where('workspaces.data.0.plan.value', 'premium'));

    $this->get('/staff/workspaces?deleted=1')->assertInertia(fn (Assert $page) => $page
        ->has('workspaces.data', 1)
        ->where('workspaces.data.0.id', $gone->id));
});

it('shows a workspace with its members and usage, deleted or not', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);

    $this->actingAs(staffSupport(), 'staff')
        ->get("/staff/workspaces/{$workspace->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/workspaces/show')
            ->where('workspace.members.0.email', $agent->email)
            ->where('workspace.members.0.role.value', 'owner')
            ->has('usage.seats')
            ->where('can_change', false));

    $workspace->delete();
    $this->get("/staff/workspaces/{$workspace->id}")->assertInertia(fn (Assert $page) => $page->where('workspace.deleted_at', fn ($value) => $value !== null));
});

it('lets admins give and end Premium, logging who did it', function () {
    $staff = staffAdmin();
    $workspace = workspaceOf(User::factory()->create());

    $this->actingAs($staff, 'staff')
        ->post("/staff/workspaces/{$workspace->id}/premium", ['days' => 30, 'note' => 'Beta tester'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($workspace->fresh()->currentPlan())->toBe(Plan::Premium)
        ->and($workspace->fresh()->plan_ends_at->diffInDays(now()->addDays(30)))->toBeLessThan(1)
        ->and($workspace->premiumGrants()->sole()->note)->toBe('Beta tester');

    $this->post("/staff/workspaces/{$workspace->id}/premium", ['days' => null]);
    expect($workspace->fresh()->plan_ends_at)->toBeNull();

    $this->delete("/staff/workspaces/{$workspace->id}/premium")->assertRedirect();
    expect($workspace->fresh()->currentPlan())->toBe(Plan::Free);

    expect(StaffActivity::orderBy('id')->get()->map(fn ($activity) => [$activity->staff_id, $activity->type])->all())->toEqual([
        [$staff->id, StaffActivityType::PremiumGranted],
        [$staff->id, StaffActivityType::PremiumGranted],
        [$staff->id, StaffActivityType::PremiumEnded],
    ]);
});

it('lets admins restore a deleted workspace', function () {
    $workspace = workspaceOf(User::factory()->create());
    $workspace->delete();

    $this->actingAs(staffAdmin(), 'staff')->post("/staff/workspaces/{$workspace->id}/restoration")->assertRedirect();

    expect($workspace->fresh()->trashed())->toBeFalse()
        ->and(StaffActivity::sole()->type)->toBe(StaffActivityType::WorkspaceRestored);
});

it('keeps support to looking things up', function () {
    $workspace = workspaceOf(User::factory()->create());

    $this->actingAs(staffSupport(), 'staff');
    $this->post("/staff/workspaces/{$workspace->id}/premium", ['days' => 30])->assertForbidden();
    $this->post('/staff/premium-links', ['days' => 30, 'max_claims' => 1])->assertForbidden();
    $workspace->delete();
    $this->post("/staff/workspaces/{$workspace->id}/restoration")->assertForbidden();

    expect($workspace->fresh()->currentPlan())->toBe(Plan::Free)
        ->and(PremiumLink::count())->toBe(0)
        ->and(StaffActivity::count())->toBe(0);
});

it('makes Premium links and lists them with their claims', function () {
    $this->actingAs(staffAdmin(), 'staff')
        ->post('/staff/premium-links', ['days' => 90, 'max_claims' => 5, 'note' => 'Beta testers', 'valid_until' => now()->addWeek()->toIso8601String()])
        ->assertRedirect();
    $this->post('/staff/premium-links', ['days' => 90, 'max_claims' => 0])->assertSessionHasErrors('max_claims');

    $link = PremiumLink::sole();
    expect($link->max_claims)->toBe(5)->and(StaffActivity::sole()->subject_id)->toBe($link->id);

    $this->get('/staff/premium-links')->assertInertia(fn (Assert $page) => $page
        ->component('staff/premium-links/index')
        ->where('links.data.0.note', 'Beta testers')
        ->where('links.data.0.claims', 0)
        ->where('links.data.0.url', config('app.frontend_url')."/premium/{$link->token}"));
});

it('finds users by name or email, with their workspaces', function () {
    $maria = User::factory()->create(['name' => 'Maria Santos']);
    User::factory()->create(['name' => 'Jose Cruz']);

    $this->actingAs(staffSupport(), 'staff')
        ->get('/staff/users?search=santos')
        ->assertInertia(fn (Assert $page) => $page
            ->component('staff/users/index')
            ->has('users.data', 1)
            ->where('users.data.0.email', $maria->email)
            ->where('users.data.0.workspaces.0.role.value', 'owner'));
});
