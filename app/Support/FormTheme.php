<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A form's look: light or dark (or the visitor's choice), accent colour, corners, field style,
 * logo, and the Premium extras (font, page background, no "Powered by Insurix"). The frontend
 * turns these into CSS variables; here they're validated and trimmed to what the plan allows.
 */
class FormTheme
{
    public const MODES = ['auto', 'light', 'dark'];

    public const RADII = ['none', 'small', 'medium', 'large'];

    public const FIELD_STYLES = ['outline', 'filled', 'underline'];

    public const FONTS = ['default', 'serif', 'rounded'];

    /** Page background presets; the frontend owns their colours in light and dark. */
    public const BACKGROUNDS = ['none', 'mist', 'sky', 'mint', 'sunrise', 'dusk'];

    /** Settings only Premium workspaces see on their public forms. */
    private const PREMIUM = ['font' => 'default', 'background' => 'none', 'hide_branding' => false];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'mode' => 'auto',
            'accent' => '#2b66dc',
            'radius' => 'medium',
            'field_style' => 'outline',
            'show_logo' => true,
            ...self::PREMIUM,
        ];
    }

    /**
     * The theme as visitors get it: missing settings take the defaults, and without Premium the
     * Premium settings fall back to theirs (the builder keeps them, so upgrading brings them back).
     *
     * @param  array<string, mixed>|null  $theme
     * @return array<string, mixed>
     */
    public static function forVisitors(?array $theme, bool $premium): array
    {
        $theme = [...self::defaults(), ...($theme ?? [])];

        return $premium ? $theme : [...$theme, ...self::PREMIUM];
    }
}
