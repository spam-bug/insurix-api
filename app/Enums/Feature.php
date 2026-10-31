<?php

declare(strict_types=1);

namespace App\Enums;

/** Features that can be switched on or off, each with its access level set in config/insurix.php. */
enum Feature: string
{
    case WorkspaceOnboarding = 'workspace-onboarding';
    case WorkspaceSwitcher = 'workspace-switcher';
    case FormPremiumTheme = 'form-premium-theme';
    case Automations = 'automations';

    public function access(): FeatureAccess
    {
        return FeatureAccess::from(config("insurix.features.{$this->value}"));
    }
}
