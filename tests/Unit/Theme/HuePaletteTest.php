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

use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\DependencyInjection\ElementConfiguration;
use UtafitiLabs\ElementBundle\Tests\Support\ColorMetric;
use UtafitiLabs\ElementBundle\Tests\Support\ProcessedTheme;

/**
 * THE HUES STAY HONEST AS THE SET GROWS (ruled 2026-09-24).
 *
 * A categorical palette is a promise that two categories LOOK different, and
 * the promise is kept by measurement, not by taste: every shipped hue stands at
 * least {@see ElementConfiguration::HUE_DISTANCE} from every other in CIE76 Lab
 * distance, and clears {@see ElementConfiguration::HUE_CONTRAST} against the
 * ground it is drawn on — in light and in dark, which are two palettes and are
 * measured separately.
 *
 * So the day somebody adds a nineteenth hue, this test says whether the set can
 * still be told apart.
 */
final class HuePaletteTest extends TestCase
{
    public function testEveryHueIsToldApartFromEveryOtherOnBothGrounds(): void
    {
        foreach (['colors' => 'light', 'dark' => 'dark'] as $where => $which) {
            $hues = self::hues($where);
            $names = array_keys($hues);

            foreach ($names as $i => $name) {
                foreach (\array_slice($names, $i + 1) as $other) {
                    self::assertGreaterThanOrEqual(
                        ElementConfiguration::HUE_DISTANCE,
                        ColorMetric::distance($hues[$name], $hues[$other]),
                        \sprintf('In %s, "%s" and "%s" are too close to tell apart.', $which, $name, $other),
                    );
                }
            }
        }
    }

    public function testEveryHueCanBeSeenAgainstTheGroundItIsDrawnOn(): void
    {
        foreach (['colors', 'dark'] as $where) {
            $ground = self::ground($where);

            foreach (self::hues($where) as $name => $value) {
                self::assertGreaterThanOrEqual(
                    ElementConfiguration::HUE_CONTRAST,
                    ColorMetric::contrast($value, $ground),
                    \sprintf('"%s" does not carry against the %s ground.', $name, $where),
                );
            }
        }
    }

    /**
     * EVERY HUE IS STATED TWICE, once for each ground. A hue with no dark value
     * would be drawn in the light one on the night canvas, where it is the one
     * category nobody can read.
     */
    public function testEveryHueHasAValueForBothGrounds(): void
    {
        self::assertSame(array_keys(self::hues('colors')), array_keys(self::hues('dark')));
        self::assertGreaterThanOrEqual(16, \count(self::hues('colors')), 'The shipped set is the one a product with many categories can lean on.');
    }

    /**
     * @return array<string, string>
     */
    private static function hues(string $where): array
    {
        $theme = ProcessedTheme::theme();

        return 'dark' === $where ? $theme['dark']['hues'] : $theme['hues'];
    }

    private static function ground(string $where): string
    {
        $theme = ProcessedTheme::theme();

        return 'dark' === $where ? $theme['dark']['colors']['ground'] : $theme['colors']['ground'];
    }
}
