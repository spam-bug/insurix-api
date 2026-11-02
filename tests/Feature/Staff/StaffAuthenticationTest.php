<?php

declare(strict_types=1);

use App\Enums\StaffRole;
use App\Models\Staff;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;

/** The code the staff member's authenticator app would show right now. */
function staffCode(Staff $staff): string
{
    return app(Google2FA::class)->getCurrentOtp(decrypt($staff->fresh()->two_factor_secret));
}

it('signs staff in with a password, then a code from their app', function () {
    $staff = Staff::factory()->withTwoFactor()->create();

    $this->post('/staff/login', ['email' => $staff->email, 'password' => 'password'])
        ->assertRedirect('/staff/two-factor-challenge');
    $this->assertGuest('staff');

    $this->post('/staff/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
    $this->assertGuest('staff');

    $this->post('/staff/two-factor-challenge', ['code' => staffCode($staff)])->assertRedirect('/staff/workspaces');
    $this->assertAuthenticatedAs($staff, 'staff');
});

it('turns away a wrong password and anyone who isn’t staff', function () {
    $staff = Staff::factory()->withTwoFactor()->create();
    $agent = User::factory()->create();

    $this->post('/staff/login', ['email' => $staff->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    $this->post('/staff/login', ['email' => $agent->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->get('/staff/two-factor-challenge')->assertRedirect('/staff/login');

    $this->actingAs($agent)->get('/staff/workspaces')->assertRedirect('/staff/login');
});

it('lets a recovery code in once, and only within a few minutes of the password', function () {
    $staff = Staff::factory()->withTwoFactor()->create();

    $this->post('/staff/login', ['email' => $staff->email, 'password' => 'password']);
    $this->post('/staff/two-factor-challenge', ['recovery_code' => 'recovery-code-one'])->assertRedirect('/staff/workspaces');
    expect($staff->fresh()->recoveryCodes())->not->toContain('recovery-code-one');

    $this->post('/staff/logout');
    $this->post('/staff/login', ['email' => $staff->email, 'password' => 'password']);
    $this->post('/staff/two-factor-challenge', ['recovery_code' => 'recovery-code-one'])->assertSessionHasErrors('recovery_code');

    $this->travel(6)->minutes();
    $this->post('/staff/two-factor-challenge', ['code' => staffCode($staff)])->assertSessionHasErrors('code');
    $this->assertGuest('staff');
});

it('makes new staff set up two-factor before the console opens', function () {
    $staff = Staff::factory()->create();

    $this->post('/staff/login', ['email' => $staff->email, 'password' => 'password'])->assertRedirect('/staff/two-factor');
    $this->get('/staff/workspaces')->assertRedirect('/staff/two-factor');
    $this->get('/staff/two-factor')->assertInertia(fn (Assert $page) => $page
        ->component('staff/two-factor-setup')
        ->where('confirmed', false)
        ->whereType('qr_code_svg', 'string'));

    $this->post('/staff/two-factor', ['code' => '000000'])->assertSessionHasErrors('code');
    $this->post('/staff/two-factor', ['code' => staffCode($staff)])->assertRedirect('/staff/two-factor');

    $this->get('/staff/two-factor')->assertInertia(fn (Assert $page) => $page->where('confirmed', true)->has('recovery_codes', 8));
    $this->get('/staff/workspaces')->assertOk();
});

it('signs out of the console without signing an agent out of the app', function () {
    $staff = Staff::factory()->withTwoFactor()->create();
    $agent = User::factory()->create();

    $this->actingAs($agent)->actingAs($staff, 'staff')->post('/staff/logout')->assertRedirect('/staff/login');

    $this->assertGuest('staff');
    $this->assertAuthenticatedAs($agent, 'web');
});

it('adds staff from the command line, with a long enough password', function () {
    $this->artisan('staff:create', ['email' => 'mark@suvvi.test', '--name' => 'Mark', '--role' => 'support'])
        ->expectsQuestion('Password (at least 12 characters)', 'short')
        ->assertFailed();

    $this->artisan('staff:create', ['email' => 'mark@suvvi.test', '--name' => 'Mark', '--role' => 'support'])
        ->expectsQuestion('Password (at least 12 characters)', 'a long enough password')
        ->assertSuccessful();

    expect(Staff::sole())->name->toBe('Mark')->role->toBe(StaffRole::Support);
});
