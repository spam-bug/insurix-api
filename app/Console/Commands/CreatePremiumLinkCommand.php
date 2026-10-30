<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Premium\CreatePremiumLink;
use App\DataTransferObject\Premium\PremiumLinkData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('premium:create-link
    {--days= : How many days of Premium each claim gives}
    {--years= : Or how many years}
    {--forever : Premium with no end date}
    {--claims=1 : How many workspaces can claim it}
    {--valid-until= : Last day the link can be claimed}
    {--email= : Only this account can claim it}
    {--note= : Why it was made, such as "Beta testers"}')]
#[Description('Make a link that gives Premium to the workspace of whoever claims it')]
class CreatePremiumLinkCommand extends Command
{
    public function handle(CreatePremiumLink $createPremiumLink): int
    {
        $days = PremiumDuration::days($this);

        if ($days === false) {
            return self::INVALID;
        }

        $link = $createPremiumLink->handle(new PremiumLinkData(
            days: $days,
            maxClaims: max(1, (int) $this->option('claims')),
            validUntil: $this->option('valid-until') ? CarbonImmutable::parse($this->option('valid-until'))->endOfDay() : null,
            email: $this->option('email'),
            note: $this->option('note'),
        ));

        $this->info(config('app.frontend_url')."/premium/{$link->token}");

        return self::SUCCESS;
    }
}
