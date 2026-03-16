<?php

use App\Jobs\ExportUsers;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('authorized roles can access user import and export', function () {
    $faculty = User::factory()->create(['role' => 'Faculty']);

    $this->actingAs($faculty)
        ->get(route('users.import.create'))
        ->assertOk();

    $this->actingAs($faculty)
        ->get(route('users.export.create'))
        ->assertOk();
});

test('students cannot access user import and export', function () {
    $student = User::factory()->create(['role' => 'Student']);

    $this->actingAs($student)
        ->get(route('users.import.create'))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');

    $this->actingAs($student)
        ->get(route('users.export.create'))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');
});

test('export queues when no role is selected', function () {
    Queue::fake();
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->post(route('users.export.store'), ['format' => 'csv'])
        ->assertRedirect(route('users.export.create'))
        ->assertSessionHas('notify.type', 'success');

    Queue::assertPushed(ExportUsers::class);

    $this->assertDatabaseHas('user_exports', [
        'user_id' => $admin->id,
        'role_filter' => null,
        'format' => 'csv',
        'status' => 'queued',
    ]);
});

test('export rejects admin and super admin roles', function () {
    $admin = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($admin)
        ->post(route('users.export.store'), ['format' => 'csv', 'role' => 'Admin'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');

    $this->actingAs($admin)
        ->post(route('users.export.store'), ['format' => 'csv', 'role' => 'Super Admin'])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');
});
