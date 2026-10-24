<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReplaceAccountImage
{
    /** Which image, and the folder it's kept in on the uploads disk. */
    /**
     * Stores the person's new profile photo and deletes the old one. Passing no file removes it.
     */
    public function handle(User $agent, ?UploadedFile $file): User
    {
        $previous = $agent->avatar_path;
        $disk = Storage::disk(User::UPLOADS_DISK);

        $path = $file?->store('avatars', User::UPLOADS_DISK);

        DB::transaction(fn () => $agent->forceFill(['avatar_path' => $path])->save());

        if ($previous) {
            $disk->delete($previous);
        }

        return $agent;
    }
}
