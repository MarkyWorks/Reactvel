<?php

use App\Imports\UsersImport;
use App\Jobs\ExportUsers;
use App\Mail\UsersExportReady;
use App\Mail\UsersImportFinished;
use App\Models\User;
use App\Models\UserExport;
use App\Models\UserImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

uses(RefreshDatabase::class);

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

test('import status endpoint returns latest import progress', function () {
    $admin = User::factory()->create(['role' => 'Admin']);

    $import = UserImport::query()->create([
        'user_id' => $admin->id,
        'file_name' => 'users-import.csv',
        'status' => 'processing',
        'uploaded_at' => now(),
        'started_at' => now(),
        'users_read' => 5,
        'users_saved' => 3,
        'errors' => ['Row 2: Email already exists.'],
    ]);

    $this->actingAs($admin)
        ->getJson(route('users.import.status', $import))
        ->assertSuccessful()
        ->assertJson([
            'status' => 'processing',
            'users_read' => 5,
            'users_saved' => 3,
            'errors' => ['Row 2: Email already exists.'],
        ]);
});

test('import enforces unique campus id, name, and email', function () {
    User::factory()->create([
        'campus_id' => '100',
        'name' => 'Alice Existing',
        'email' => 'alice@example.com',
        'role' => 'Student',
    ]);

    $import = new UsersImport(null);
    $import->collection(collect([
        [
            'campus_id' => '100',
            'name' => 'New Name',
            'email' => 'new1@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '101',
            'name' => 'Alice Existing',
            'email' => 'new2@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '102',
            'name' => 'Bob Name',
            'email' => 'alice@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '103',
            'name' => 'Bob Name',
            'email' => 'bob@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '104',
            'name' => 'Cara New',
            'email' => 'cara@example.com',
            'role' => 'Student',
        ],
    ]));

    expect($import->errors())->toContain(
        'Row 2: Campus ID already exists.',
        'Row 3: Name already exists.',
        'Row 4: Email already exists.',
        'Row 5: Name already exists in this file.',
    );

    $this->assertDatabaseHas('users', [
        'email' => 'cara@example.com',
        'campus_id' => '104',
        'name' => 'Cara New',
    ]);
});

test('import requires campus id, name, email, and role', function () {
    $import = new UsersImport(null);
    $import->collection(collect([
        [
            'campus_id' => '',
            'name' => 'Missing Campus',
            'email' => 'missing-campus@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '200',
            'name' => '',
            'email' => 'missing-name@example.com',
            'role' => 'Student',
        ],
        [
            'campus_id' => '201',
            'name' => 'Missing Email',
            'email' => '',
            'role' => 'Student',
        ],
        [
            'campus_id' => '202',
            'name' => 'Missing Role',
            'email' => 'missing-role@example.com',
            'role' => '',
        ],
    ]));

    expect($import->errors())->toContain(
        'Row 2: campus_id, name, email, and role are required.',
        'Row 3: campus_id, name, email, and role are required.',
        'Row 4: campus_id, name, email, and role are required.',
        'Row 5: campus_id, name, email, and role are required.',
    );
});

test('import reports all existing field errors on the same row', function () {
    User::factory()->create([
        'campus_id' => '200',
        'name' => 'Multi Existing',
        'email' => 'multi@example.com',
        'role' => 'Student',
    ]);

    $import = new UsersImport(null);
    $import->collection(collect([
        [
            'campus_id' => '200',
            'name' => 'Multi Existing',
            'email' => 'multi@example.com',
            'role' => 'Student',
        ],
    ]));

    expect($import->errors())->toContain(
        'Row 2: Email already exists.',
        'Row 2: Campus ID already exists.',
        'Row 2: Name already exists.',
    );
});

test('import generates a name-based password when missing', function () {
    Carbon::setTestNow('2026-03-17 00:00:00');

    $import = new UsersImport(null);
    $import->collection(collect([
        [
            'campus_id' => '300',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'Student',
        ],
    ]));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect(Hash::check('Jane'.now()->year, $user->password))->toBeTrue();

    Carbon::setTestNow();
});

test('import request rejects rows that already exist', function () {
    Queue::fake();
    $admin = User::factory()->create(['role' => 'Admin']);

    $content = implode("\n", [
        'campus_id,name,email,role,password',
        '100,Alpha User,alpha@example.com,Student,secret',
        '100,Beta User,beta@example.com,Student,secret',
        '101,Gamma User,gamma@example.com,Student,secret',
    ]);

    $file = UploadedFile::fake()->createWithContent('users.csv', $content);

    $response = $this->actingAs($admin)->post(route('users.import.store'), [
        'users_file' => $file,
    ]);

    $response->assertSessionHas('notify.type', 'success');
    Queue::assertPushed(\App\Jobs\ImportUsers::class);
});

test('import job stores exception in errors array when it fails', function () {
    Storage::fake('local');
    ExcelFacade::shouldReceive('import')
        ->once()
        ->andThrow(new RuntimeException('Import exploded'));

    $admin = User::factory()->create(['role' => 'Admin']);

    $import = UserImport::query()->create([
        'user_id' => $admin->id,
        'file_name' => 'users-import.csv',
        'status' => 'queued',
        'uploaded_at' => now(),
    ]);

    $job = new \App\Jobs\ImportUsers('imports/users/file.csv', $admin->id, $import->id);

    try {
        $job->handle();
    } catch (RuntimeException $exception) {
        // Expected.
    }

    $import->refresh();

    expect($import->status)->toBe('failed');
    expect($import->errors)->toBeArray()->not()->toBeEmpty();
    expect($import->errors[0])->toContain(RuntimeException::class);
});

test('import job emails saved records to the requester when finished', function () {
    Mail::fake();
    Storage::fake('local');
    ExcelFacade::shouldReceive('import')
        ->once()
        ->andReturnUsing(function ($handler, $path) {
            $handler->collection(collect([
                [
                    'campus_id' => '501',
                    'name' => 'Email User',
                    'email' => 'emailuser@example.com',
                    'role' => 'Student',
                ],
            ]));
        });

    $admin = User::factory()->create(['role' => 'Admin']);
    $import = UserImport::query()->create([
        'user_id' => $admin->id,
        'file_name' => 'import.csv',
        'status' => 'queued',
        'uploaded_at' => now(),
    ]);

    $job = new \App\Jobs\ImportUsers('imports/users/file.csv', $admin->id, $import->id);
    $job->handle();

    Mail::assertSent(UsersImportFinished::class, function (UsersImportFinished $mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });
});

test('import job does not email when there are errors and zero saved rows', function () {
    Mail::fake();
    Storage::fake('local');
    ExcelFacade::shouldReceive('import')
        ->once()
        ->andReturnUsing(function ($handler, $path) {
            $handler->collection(collect([
                [
                    'campus_id' => '700',
                    'name' => '',
                    'email' => '',
                    'role' => 'Student',
                ],
            ]));
        });

    $admin = User::factory()->create(['role' => 'Admin']);
    $import = UserImport::query()->create([
        'user_id' => $admin->id,
        'file_name' => 'import.csv',
        'status' => 'queued',
        'uploaded_at' => now(),
    ]);

    $job = new \App\Jobs\ImportUsers('imports/users/file.csv', $admin->id, $import->id);
    $job->handle();

    Mail::assertNotSent(UsersImportFinished::class);
});
