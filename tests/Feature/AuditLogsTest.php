<?php

use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

test('admins can view audit logs with activity statuses', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-11 12:00:00'));

    $inactiveUser = User::factory()->create();
    $inactiveUser->forceFill([
        'last_seen_at' => now()->subMinutes(20),
    ])->save();

    $offlineUser = User::factory()->create();
    $offlineUser->forceFill([
        'last_seen_at' => now()->subHours(2),
    ])->save();

    $admin = User::factory()->create(['role' => 'Admin']);

    AuditLog::factory()->create([
        'user_id' => $admin->id,
        'action' => 'Login',
    ]);

    $response = $this->actingAs($admin)->get(route('audit-logs.index'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('audit-logs/index')
        ->has('users.data')
        ->has('logs.data', 1)
    );

    $users = collect(data_get($response->viewData('page'), 'props.users.data', []));

    expect($users->firstWhere('id', $inactiveUser->id)['status'])->toBe('inactive')
        ->and($users->firstWhere('id', $offlineUser->id)['status'])->toBe('offline')
        ->and($users->firstWhere('id', $offlineUser->id)['last_active_label'])->not->toBeNull();

    Carbon::setTestNow();
});

test('audit logs supports search and status filters', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-11 12:00:00'));

    $onlineUser = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);
    $onlineUser->forceFill([
        'last_seen_at' => now()->subSeconds(10),
        'last_logged_out_at' => null,
    ])->save();

    $offlineUser = User::factory()->create([
        'name' => 'Offline User',
        'email' => 'offline@example.com',
    ]);
    $offlineUser->forceFill([
        'last_seen_at' => now()->subHours(2),
    ])->save();

    $admin = User::factory()->create(['role' => 'Admin']);

    AuditLog::factory()->create([
        'user_id' => $onlineUser->id,
        'action' => 'Login',
    ]);

    $response = $this->actingAs($admin)->get(route('audit-logs.index', [
        'search' => 'Admin',
        'status' => 'online',
    ]));

    $users = collect(data_get($response->viewData('page'), 'props.users.data', []));
    $logs = collect(data_get($response->viewData('page'), 'props.logs.data', []));

    expect($users)->toHaveCount(1)
        ->and($users->first()['id'])->toBe($onlineUser->id)
        ->and($logs)->toHaveCount(1)
        ->and($logs->first()['user'])->toBe('Admin User');

    Carbon::setTestNow();
});

test('login audit logs do not duplicate within a short window', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-11 12:00:00'));

    $user = User::factory()->create();
    $listener = new LogUserLogin;
    $event = new Login('web', $user, false);

    $listener->handle($event);
    $listener->handle($event);

    expect(AuditLog::query()->where('user_id', $user->id)->where('action', 'Login')->count())
        ->toBe(1);

    Carbon::setTestNow();
});

test('offline users use logged out timestamp for last activity', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-11 12:00:00'));

    $user = User::factory()->create();
    $user->forceFill([
        'last_seen_at' => now()->subMinutes(10),
        'last_logged_out_at' => now()->subMinutes(2),
    ])->save();

    $admin = User::factory()->create(['role' => 'Admin']);

    $response = $this->actingAs($admin)->get(route('audit-logs.index'));
    $users = collect(data_get($response->viewData('page'), 'props.users.data', []));
    $payload = $users->firstWhere('id', $user->id);

    expect($payload['status'])->toBe('offline')
        ->and($payload['last_active_at'])->toBe($user->last_logged_out_at?->toDateTimeString());

    Carbon::setTestNow();
});

test('logout marks user status as offline', function () {
    $user = User::factory()->create([
        'last_seen_at' => now(),
    ]);

    $listener = new LogUserLogout;
    $event = new Logout('web', $user);

    $listener->handle($event);

    expect($user->fresh()->last_logged_out_at)->not->toBeNull();
});
