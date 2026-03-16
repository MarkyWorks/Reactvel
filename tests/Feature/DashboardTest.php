<?php

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard provides user management metrics', function () {
    $user = User::factory()->create();
    AuditLog::factory()->create([
        'user_id' => $user->id,
        'action' => 'Login',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('dashboard')
        ->has('kpis.total_users')
        ->has('kpis.active_today')
        ->has('kpis.new_users')
        ->has('kpis.admins')
        ->has('recentActivity')
        ->has('recentUsers')
        ->has('roleDistribution')
        ->has('securitySnapshot.logins_last_24h')
        ->has('securitySnapshot.logouts_last_24h')
    );
});
