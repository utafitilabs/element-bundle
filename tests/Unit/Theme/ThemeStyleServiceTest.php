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

namespace UtafitiLabs\ElementBundle\Tests\Unit\Theme;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\Tests\Support\ProcessedTheme;
use UtafitiLabs\ElementBundle\Theme\ThemeStyleService;

/**
 * THE BLOCK A PAGE PUTS IN ITS HEAD.
 *
 * What is asserted here is the SEAM: the property names the built stylesheet
 * spends, the two selectors, and the fact that a configured value arrives
 * verbatim. Which colours those are is the installation's business and is
 * asserted nowhere.
 */
#[CoversClass(ThemeStyleService::class)]
final class ThemeStyleServiceTest extends TestCase
{
    public function testTheBlockIsOneStyleElementWithBothPalettes(): void
    {
        $block = ProcessedTheme::service()->render();

        self::assertStringStartsWith('<style>', $block);
        self::assertStringEndsWith("</style>\n", $block);
        self::assertStringContainsString(':root {', $block);
        self::assertStringContainsString('html.dark {', $block);
        self::assertSame(1, substr_count($block, '<style>'));
    }

    public function testEveryValueOfTheThemeIsRenderedUnderTheBundlesOwnPrefix(): void
    {
        $css = ProcessedTheme::service()->css();

        foreach ([
            '--e-ground', '--e-surface', '--e-surface-low', '--e-raised',
            '--e-ink', '--e-muted', '--e-faint',
            '--e-accent', '--e-accent-ink',
            '--e-ok', '--e-warn', '--e-danger', '--e-danger-ink', '--e-critical',
            '--e-line', '--e-line-strong', '--e-shadow',
            '--e-font-ui', '--e-font-mono', '--e-radius', '--e-control-height',
        ] as $property) {
            self::assertStringContainsString($property.':', $css, $property.' is spent by the stylesheet and rendered by nobody.');
        }
    }

    public function testTheMeasurementsAreStatedOnceAndThePaletteTwice(): void
    {
        $css = ProcessedTheme::service()->css();

        self::assertSame(1, substr_count($css, '--e-radius:'), 'Dark changes the palette, never the measurements.');
        self::assertSame(1, substr_count($css, '--e-font-ui:'));
        self::assertSame(2, substr_count($css, '--e-accent:'), 'Light and dark are two complete palettes.');
    }

    public function testAConfiguredValueArrivesVerbatim(): void
    {
        $css = ProcessedTheme::service(['theme' => [
            'colors' => ['accent' => 'oklch(0.7 0.1 200)'],
            'radius' => '0',
            'fonts' => ['ui' => 'Georgia, serif'],
            'dark' => ['colors' => ['accent' => 'white']],
        ]])->css();

        self::assertStringContainsString('--e-accent: oklch(0.7 0.1 200);', $css);
        self::assertStringContainsString('--e-radius: 0;', $css);
        self::assertStringContainsString('--e-font-ui: Georgia, serif;', $css);
        self::assertStringContainsString('--e-accent: white;', $css);
    }

    /**
     * THE HUES COME OUT TWICE: by the name that carries a meaning, and by the
     * index a series is coloured from — the index being an alias, so a restated
     * hue is restated once.
     */
    public function testTheHuesAreRenderedByNameAndByIndex(): void
    {
        $css = ProcessedTheme::service()->css();

        self::assertStringContainsString('--e-hue-moss: #1E7A46;', $css);
        self::assertStringContainsString('--e-hue-1: var(--e-hue-moss);', $css);
        self::assertStringContainsString('--e-hue-18: var(--e-hue-orchid);', $css);
        self::assertStringContainsString('--e-hue-moss: #4FA86A;', $css, 'The hues have a dark value too.');
        self::assertSame(1, substr_count($css, '--e-hue-1:'), 'The index form is an alias, stated once.');
    }

    public function testAProductMayRestateAHueAndAppendItsOwn(): void
    {
        $css = ProcessedTheme::service(['theme' => [
            'hues' => ['moss' => '#001100', 'seagrass' => '#004433'],
            'dark' => ['hues' => ['seagrass' => '#66CCAA']],
        ]])->css();

        self::assertStringContainsString('--e-hue-moss: #001100;', $css);
        self::assertStringContainsString('--e-hue-seagrass: #004433;', $css);
        self::assertStringContainsString('--e-hue-seagrass: #66CCAA;', $css);
        self::assertStringContainsString('--e-hue-ochre: #8A6410;', $css, 'Stating one hue keeps the rest of the shipped set.');
        self::assertStringContainsString('--e-hue-19: var(--e-hue-seagrass);', $css, 'An appended hue is numbered after the shipped ones.');
    }
}
