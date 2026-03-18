<?php

namespace App\Http\Controllers;

use App\Enums\User\UserRoleEnum;
use App\Http\Requests\Users\ImportRequest;
use App\Jobs\ImportUsers;
use App\Models\UserImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersImportController extends Controller
{
    private function canImportUsers(?\App\Models\User $user): bool
    {
        return in_array($user?->role, [UserRoleEnum::SuperAdmin, UserRoleEnum::Admin], true);
    }

    private function denyIfCannotImport(Request $request): ?RedirectResponse
    {
        if ($this->canImportUsers($request->user())) {
            return null;
        }

        return redirect()
            ->route('users.index')
            ->with('notify', [
                'type' => 'error',
                'message' => 'You are not authorized to import users.',
            ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($response = $this->denyIfCannotImport($request)) {
            return $response;
        }

        $uploadedDate = (string) $request->string('uploaded_date')->trim();

        $imports = UserImport::query()
            ->with('user:id,email')
            ->when($uploadedDate !== '', function ($query) use ($uploadedDate) {
                $query->whereDate('uploaded_at', $uploadedDate);
            })
            ->latest('uploaded_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (UserImport $import) => [
                'id' => $import->id,
                'uploaded_at' => $import->uploaded_at?->toDateTimeString(),
                'user_email' => $import->user?->email,
                'file_name' => $import->file_name,
                'started_at' => $import->started_at?->toDateTimeString(),
                'finished_at' => $import->finished_at?->toDateTimeString(),
                'users_read' => $import->users_read,
                'users_saved' => $import->users_saved,
                'status' => $import->status,
                'error_message' => $import->error_message,
                'errors' => $import->errors ?? [],
                'status_url' => route('users.import.status', $import),
            ]);

        return Inertia::render('users/import', [
            'imports' => $imports,
            'uploadedDate' => $uploadedDate,
        ]);
    }

    public function store(ImportRequest $request): RedirectResponse
    {
        $file = $request->file('users_file');
        $path = $file->storeAs('imports/users', $file->hashName());

        $import = UserImport::query()->create([
            'user_id' => $request->user()?->id,
            'file_name' => $file->getClientOriginalName(),
            'status' => 'processing',
            'uploaded_at' => now(),
        ]);

        ImportUsers::dispatch($path, $request->user()?->id, $import->id);

        return back()->with('notify', [
            'type' => 'success',
            'message' => 'User import queued. We will notify you when it is complete.',
        ]);
    }

    public function status(Request $request, UserImport $userImport): JsonResponse|RedirectResponse
    {
        if ($response = $this->denyIfCannotImport($request)) {
            return $response;
        }

        return response()->json([
            'status' => $userImport->status,
            'started_at' => $userImport->started_at?->toDateTimeString(),
            'finished_at' => $userImport->finished_at?->toDateTimeString(),
            'users_read' => $userImport->users_read,
            'users_saved' => $userImport->users_saved,
            'error_message' => $userImport->error_message,
            'errors' => $userImport->errors ?? [],
            'done' => in_array($userImport->status, ['finished', 'failed'], true),
        ]);
    }
}
