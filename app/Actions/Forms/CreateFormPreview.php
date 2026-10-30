<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Models\Form;
use Illuminate\Support\Facades\Crypt;

class CreateFormPreview
{
    /** How long a preview link works. */
    public const MINUTES = 30;

    /**
     * A short-lived token that shows the form's draft on its public page, so the agent sees it
     * exactly as visitors will before publishing. It's encrypted, so it can't be made up.
     */
    public function handle(Form $form): string
    {
        return Crypt::encryptString(json_encode(['form' => $form->id, 'expires' => now()->addMinutes(self::MINUTES)->timestamp]));
    }
}
