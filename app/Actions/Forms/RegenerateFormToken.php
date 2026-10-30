<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Models\Form;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegenerateFormToken
{
    /** A new link for the form, so any link shared before stops working. */
    public function handle(Form $form): Form
    {
        DB::transaction(fn () => $form->update(['token' => Str::random(40)]));

        Cache::tags("workspace:{$form->workspace_id}")->flush();

        return $form;
    }
}
