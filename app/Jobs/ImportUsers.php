<?php

namespace App\Jobs;

use App\Imports\UsersImport;
use App\Mail\UsersImportFinished;
use App\Models\UserImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $path,
        private ?string $userId,
        private string $importId
    ) {}

    public function handle(): void
    {
        $filePath = Storage::disk('local')->path($this->path);
        $import = UserImport::query()->find($this->importId);

        if (! $import) {
            return;
        }

        $import->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
            'errors' => [],
        ])->save();

        try {
            $importHandler = new UsersImport($this->userId);
            Excel::import($importHandler, $filePath);

            $import->forceFill([
                'status' => 'finished',
                'finished_at' => now(),
                'users_read' => $importHandler->usersRead(),
                'users_saved' => $importHandler->usersSaved(),
                'errors' => $importHandler->errors(),
            ])->save();

            if ($import->users_saved > 0 && $import->user) {
                Mail::to($import->user)->send(new UsersImportFinished($import));
            }
        } catch (\Throwable $exception) {
            $import->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => 'User import failed. See errors for details.',
                'errors' => [sprintf('%s: %s', $exception::class, $exception->getMessage())],
            ])->save();

            throw $exception;
        }
    }
}
