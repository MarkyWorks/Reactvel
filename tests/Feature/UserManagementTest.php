<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('verified users can visit the user management page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();
});

test('admins can visit create and edit user pages', function () {
    $user = User::factory()->create(['role' => 'Admin']);
    $target = User::factory()->create(['role' => 'User']);

    $this->actingAs($user)
        ->get(route('users.create'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('users.edit', $target))
        ->assertOk();
});

test('admins can create a user', function () {
    $user = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($user)
        ->post(route('users.store'), [
            'name' => 'Executive One',
            'email' => 'executive.one@example.com',
            'role' => 'Admin',
            'password' => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'success');

    $this->assertDatabaseHas('users', [
        'name' => 'Executive One',
        'email' => 'executive.one@example.com',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'Create User',
    ]);
});

test('admins can update a user', function () {
    $user = User::factory()->create(['role' => 'Admin']);
    $target = User::factory()->create(['role' => 'User']);

    $this->actingAs($user)
        ->put(route('users.update', $target), [
            'name' => 'Updated Executive',
            'email' => 'updated.executive@example.com',
            'role' => 'User',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'success');

    expect($target->fresh()->name)->toBe('Updated Executive')
        ->and($target->fresh()->email)->toBe('updated.executive@example.com')
        ->and(Hash::check('NewPassword123!', $target->fresh()->password))->toBeTrue();
});

test('admins can update a user without changing password', function () {
    $user = User::factory()->create(['role' => 'Admin']);
    $target = User::factory()->create([
        'role' => 'User',
        'password' => 'original-password',
    ]);
    $originalPasswordHash = $target->password;

    $this->actingAs($user)
        ->put(route('users.update', $target), [
            'name' => 'Updated Without Password',
            'email' => 'updated.without.password@example.com',
            'role' => 'User',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'success');

    expect($target->fresh()->name)->toBe('Updated Without Password')
        ->and($target->fresh()->email)->toBe('updated.without.password@example.com')
        ->and($target->fresh()->password)->toBe($originalPasswordHash);
});

test('admins can delete another user', function () {
    $user = User::factory()->create(['role' => 'Admin']);
    $target = User::factory()->create(['role' => 'User']);

    $this->actingAs($user)
        ->delete(route('users.destroy', $target))
        ->assertRedirect()
        ->assertSessionHas('notify.type', 'success');

    $this->assertDatabaseMissing('users', [
        'id' => $target->id,
    ]);
});

test('admins cannot delete their own active account from user management', function () {
    $user = User::factory()->create(['role' => 'Admin']);

    $this->actingAs($user)
        ->delete(route('users.destroy', $user))
        ->assertRedirect()
        ->assertSessionHas('notify.type', 'error');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});

test('admins cannot edit super admin accounts', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $superAdmin = User::factory()->create(['role' => 'Super Admin']);

    $this->actingAs($admin)
        ->get(route('users.edit', $superAdmin))
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');

    $this->actingAs($admin)
        ->put(route('users.update', $superAdmin), [
            'name' => 'Blocked Update',
            'email' => 'blocked.update@example.com',
            'role' => 'Super Admin',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'error');

    expect($superAdmin->fresh()->name)->not->toBe('Blocked Update')
        ->and($superAdmin->fresh()->email)->not->toBe('blocked.update@example.com');
});

test('admins cannot delete super admin accounts', function () {
    $admin = User::factory()->create(['role' => 'Admin']);
    $superAdmin = User::factory()->create(['role' => 'Super Admin']);

    $this->actingAs($admin)
        ->delete(route('users.destroy', $superAdmin))
        ->assertRedirect()
        ->assertSessionHas('notify.type', 'error');

    $this->assertDatabaseHas('users', [
        'id' => $superAdmin->id,
    ]);
});

test('database prevents deleting super admin users (postgres)', function () {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Postgres-only trigger test.');
    }

    $superAdmin = User::factory()->create(['role' => 'Super Admin']);

    $this->expectException(QueryException::class);

    $superAdmin->delete();
});
