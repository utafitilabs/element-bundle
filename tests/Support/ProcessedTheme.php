<?php

declare(strict_types=1);

/*
 * This file is part of the UtafitiLabs Element Bundle.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UtafitiLabs\ElementBundle\Tests\Support;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use UtafitiLabs\ElementBundle\DependencyInjection\ElementConfiguration;
use UtafitiLabs\ElementBundle\Theme\ThemeStyleService;

/**
 * The `element:` tree, processed the way the container processes it — so every
 * test that needs a theme reads the SAME defaults an installation gets, rather
 * than a second copy of them written out by hand.
 *
 * @phpstan-type Palette array{colors: array<string, string>, hues: array<string, string>, shadow: string}
 * @phpstan-type Theme array{colors: array<string, string>, hues: array<string, string>, fonts: array<string, string>, shadow: string, radius: string, control_height: string, dark: Palette}
 */
final class ProcessedTheme
{
    /**
     * @param array<string, mixed> $config what an installation writes in config/packages/element.yaml
     *
     * @return array<array-key, mixed> the merged, defaulted configuration, as the Processor returns it
     */
    public static function process(array $config = []): array
    {
        $tree = new TreeBuilder('element');
        ElementConfiguration::define($tree->getRootNode());

        return (new Processor())->process($tree->buildTree(), [$config]);
    }

    /**
     * The theme, narrowed to the shape the tree guarantees.
     *
     * @param array<string, mixed> $config
     *
     * @return Theme
     */
    public static function theme(array $config = []): array
    {
        $theme = self::process($config)['theme'] ?? null;
        $dark = self::part($theme, 'dark');

        return [
            'colors' => self::strings(self::part($theme, 'colors')),
            'hues' => self::strings(self::part($theme, 'hues')),
            'fonts' => self::strings(self::part($theme, 'fonts')),
            'shadow' => self::text(self::part($theme, 'shadow')),
            'radius' => self::text(self::part($theme, 'radius')),
            'control_height' => self::text(self::part($theme, 'control_height')),
            'dark' => [
                'colors' => self::strings(self::part($dark, 'colors')),
                'hues' => self::strings(self::part($dark, 'hues')),
                'shadow' => self::text(self::part($dark, 'shadow')),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function emptyText(array $config = []): string
    {
        return self::text(self::part(self::process($config)['table'] ?? null, 'empty_text'));
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function service(array $config = []): ThemeStyleService
    {
        $theme = self::theme($config);

        return new ThemeStyleService(
            $theme['colors'],
            $theme['dark']['colors'],
            $theme['hues'],
            $theme['dark']['hues'],
            $theme['fonts'],
            $theme['shadow'],
            $theme['dark']['shadow'],
            $theme['radius'],
            $theme['control_height'],
        );
    }

    private static function part(mixed $value, string $key): mixed
    {
        return \is_array($value) ? ($value[$key] ?? null) : null;
    }

    /**
     * @return array<string, string>
     */
    private static function strings(mixed $value): array
    {
        $strings = [];

        foreach (\is_array($value) ? $value : [] as $key => $one) {
            if (\is_string($key) && \is_string($one)) {
                $strings[$key] = $one;
            }
        }

        return $strings;
    }

    private static function text(mixed $value): string
    {
        return \is_string($value) ? $value : '';
    }
}
