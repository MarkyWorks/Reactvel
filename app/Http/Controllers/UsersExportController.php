<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Http\Requests\Users\ExportRequest;
use App\Jobs\ExportUsers;
use App\Models\UserExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UsersExportController extends Controller
{
    private function canExportUsers(?\App\Models\User $user): bool
    {
        return in_array($user?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin, UserRoleEnum::Faculty], true);
    }

    private function denyIfCannotExport(Request $request): ?RedirectResponse
    {
        if ($this->canExportUsers($request->user())) {
            return null;
        }

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'error',
                'message' => 'You are not authorized to export users.',
            ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($response = $this->denyIfCannotExport($request)) {
            return $response;
        }

        $requestedDate = (string) $request->string('requested_date')->trim();

        $exports = UserExport::query()
            ->with('user:id,email')
            ->when($requestedDate !== '', function ($query) use ($requestedDate) {
                $query->whereDate('requested_at', $requestedDate);
            })
            ->latest('requested_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserExport $export) => [
                'id' => $export->id,
                'requested_at' => $export->requested_at?->toDateTimeString(),
                'user_email' => $export->user?->email,
                'file_name' => $export->file_name,
                'format' => $export->format,
                'role_filter' => $export->role_filter,
                'started_at' => $export->started_at?->toDateTimeString(),
                'finished_at' => $export->finished_at?->toDateTimeString(),
                'users_exported' => $export->users_exported,
                'status' => $export->status,
                'error_message' => $export->error_message,
                'status_url' => route('users.export.status', $export),
                'download_url' => $export->status === 'finished'
                    ? route('users.export.download', $export)
                    : null,
            ]);

        return Inertia::render('users/export', [
            'exports' => $exports,
            'requestedDate' => $requestedDate,
        ]);
    }

    public function store(ExportRequest $request): RedirectResponse
    {
        if ($response = $this->denyIfCannotExport($request)) {
            return $response;
        }

        $role = (string) $request->string('role')->trim();
        $exportableRoles = [UserRoleEnum::Faculty->value, UserRoleEnum::Student->value];

        if ($role !== '' && ! in_array($role, $exportableRoles, true)) {
            return redirect()->route('users.index')->with('notify', [
                'type' => 'error',
                'message' => 'Please select a role to export (Student or Faculty).',
            ]);
        }

        $roles = $role === '' ? $exportableRoles : [$role];
        $format = strtolower((string) $request->string('format')->trim());

        $export = UserExport::query()->create([
            'user_id' => $request->user()?->id,
            'file_name' => sprintf('users-export-%s.%s', now()->format('Y-m-d-His'), $format),
            'format' => $format,
            'role_filter' => $role !== '' ? $role : null,
            'status' => 'queued',
            'requested_at' => now(),
        ]);

        ExportUsers::dispatch($export->id, $roles, $format);

        return redirect()->route('users.export.create')->with('notify', [
            'type' => 'success',
            'message' => 'User export queued. We will notify you when it is complete.',
        ]);
    }

    public function status(Request $request, UserExport $userExport): JsonResponse|RedirectResponse
    {
        if ($response = $this->denyIfCannotExport($request)) {
            return $response;
        }

        $downloadUrl = null;
        if ($userExport->status === 'finished' && $userExport->file_path && Storage::disk('local')->exists($userExport->file_path)) {
            $downloadUrl = route('users.export.download', $userExport);
        }

        return response()->json([
            'status' => $userExport->status,
            'started_at' => $userExport->started_at?->toDateTimeString(),
            'finished_at' => $userExport->finished_at?->toDateTimeString(),
            'users_exported' => $userExport->users_exported,
            'error_message' => $userExport->error_message,
            'download_url' => $downloadUrl,
            'done' => in_array($userExport->status, ['finished', 'failed'], true),
        ]);
    }

    public function download(Request $request, UserExport $userExport): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
    {
        if ($response = $this->denyIfCannotExport($request)) {
            return $response;
        }

        if ($userExport->status !== 'finished' || ! $userExport->file_path) {
            return redirect()->route('users.export.create')->with('notify', [
                'type' => 'error',
                'message' => 'Export file is not ready yet.',
            ]);
        }

        if (! Storage::disk('local')->exists($userExport->file_path)) {
            return redirect()->route('users.export.create')->with('notify', [
                'type' => 'error',
                'message' => 'Export file is missing. Please export again.',
            ]);
        }

        return Storage::disk('local')->download($userExport->file_path, $userExport->file_name);
    }
}
