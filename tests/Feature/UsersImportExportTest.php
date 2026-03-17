<?php

use App\Jobs\ExportUsers;
use App\Mail\UsersExportReady;
use App\Models\User;
use App\Models\UserExport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

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

test('faculty export status does not include a download url', function () {
    Storage::fake('local');
    $faculty = User::factory()->create(['role' => 'Faculty']);

    $export = UserExport::query()->create([
        'user_id' => $faculty->id,
        'file_name' => 'users-export-test.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'finished',
        'file_path' => 'exports/users/users-export-test.csv',
        'users_exported' => 1,
        'requested_at' => now(),
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    Storage::disk('local')->put($export->file_path, 'test');

    $response = $this->actingAs($faculty)->getJson(route('users.export.status', $export));

    $response->assertSuccessful();
    $response->assertJson([
        'delivery_method' => 'email',
        'download_url' => null,
    ]);
});

test('faculty export job emails the requester when complete', function () {
    Mail::fake();
    Storage::fake('local');
    ExcelFacade::shouldReceive('store')
        ->once()
        ->andReturnUsing(function ($export, $path, $disk, $writerType) {
            Storage::disk($disk)->put($path, 'test');

            return true;
        });

    $faculty = User::factory()->create(['role' => 'Faculty']);

    $export = UserExport::query()->create([
        'user_id' => $faculty->id,
        'file_name' => 'users-export-test.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'queued',
        'requested_at' => now(),
    ]);

    $job = new ExportUsers($export->id, ['Faculty'], 'csv');
    $job->handle();

    Mail::assertSent(UsersExportReady::class, function (UsersExportReady $mail) use ($faculty) {
        return $mail->hasTo($faculty->email);
    });

    $this->assertDatabaseHas('user_exports', [
        'id' => $export->id,
        'status' => 'finished',
        'error_message' => null,
    ]);
});

test('faculty only sees their own exports in the history table', function () {
    $faculty = User::factory()->create(['role' => 'Faculty']);
    $otherFaculty = User::factory()->create(['role' => 'Faculty']);

    UserExport::query()->create([
        'user_id' => $faculty->id,
        'file_name' => 'users-export-faculty.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'queued',
        'requested_at' => now(),
    ]);

    UserExport::query()->create([
        'user_id' => $otherFaculty->id,
        'file_name' => 'users-export-other.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'queued',
        'requested_at' => now(),
    ]);

    $response = $this->actingAs($faculty)->get(route('users.export.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/export')
        ->has('exports.data', 1)
        ->where('exports.data.0.user_email', $faculty->email)
    );
});

test('admin can view other exports but cannot download them', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'Admin']);
    $otherAdmin = User::factory()->create(['role' => 'Admin']);

    $export = UserExport::query()->create([
        'user_id' => $otherAdmin->id,
        'file_name' => 'users-export-other.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'finished',
        'file_path' => 'exports/users/users-export-other.csv',
        'users_exported' => 1,
        'requested_at' => now(),
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    Storage::disk('local')->put($export->file_path, 'test');

    $response = $this->actingAs($admin)->get(route('users.export.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('users/export')
        ->has('exports.data', 1)
        ->where('exports.data.0.download_url', null)
    );
});

test('users cannot download exports that are not theirs', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'Admin']);
    $otherAdmin = User::factory()->create(['role' => 'Admin']);

    $export = UserExport::query()->create([
        'user_id' => $otherAdmin->id,
        'file_name' => 'users-export-other.csv',
        'format' => 'csv',
        'role_filter' => null,
        'status' => 'finished',
        'file_path' => 'exports/users/users-export-other.csv',
        'users_exported' => 1,
        'requested_at' => now(),
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    Storage::disk('local')->put($export->file_path, 'test');

    $this->actingAs($admin)
        ->get(route('users.export.download', $export))
        ->assertRedirect(route('users.export.create'))
        ->assertSessionHas('notify.type', 'error');
});
