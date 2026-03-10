<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('verified users can visit the user management page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertOk();
});

test('verified users can visit create and edit user pages', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.create'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('users.edit', $target))
        ->assertOk();
});

test('verified users can create a user', function () {
    $user = User::factory()->create();

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
});

test('verified users can update a user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

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

test('verified users can update a user without changing password', function () {
    $user = User::factory()->create();
    $target = User::factory()->create([
        'password' => 'original-password',
    ]);
    $originalPasswordHash = $target->password;

    $this->actingAs($user)
        ->put(route('users.update', $target), [
            'name' => 'Updated Without Password',
            'email' => 'updated.without.password@example.com',
            'role' => 'Super Admin',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('notify.type', 'success');

    expect($target->fresh()->name)->toBe('Updated Without Password')
        ->and($target->fresh()->email)->toBe('updated.without.password@example.com')
        ->and($target->fresh()->password)->toBe($originalPasswordHash);
});

test('verified users can delete another user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('users.destroy', $target))
        ->assertRedirect()
        ->assertSessionHas('notify.type', 'success');

    $this->assertDatabaseMissing('users', [
        'id' => $target->id,
    ]);
});

test('verified users cannot delete their own active account from user management', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('users.destroy', $user))
        ->assertRedirect()
        ->assertSessionHas('notify.type', 'error');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);
});
