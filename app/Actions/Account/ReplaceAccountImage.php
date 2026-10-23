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
    private const FOLDERS = ['avatar' => 'avatars', 'logo' => 'logos'];

    /**
     * Stores the agent's new profile photo or logo and deletes the old one. Passing no file
     * removes the image.
     *
     * @param  'avatar'|'logo'  $image
     */
    public function handle(User $agent, string $image, ?UploadedFile $file): User
    {
        $column = "{$image}_path";
        $previous = $agent->{$column};
        $disk = Storage::disk(User::UPLOADS_DISK);

        $path = $file?->store(self::FOLDERS[$image], User::UPLOADS_DISK);

        DB::transaction(fn () => $agent->forceFill([$column => $path])->save());

        if ($previous) {
            $disk->delete($previous);
        }

        return $agent;
    }
}
