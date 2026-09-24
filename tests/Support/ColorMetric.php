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

/**
 * THE TWO MEASUREMENTS A CATEGORICAL PALETTE HAS TO PASS.
 *
 * 1. **Distance** — how far apart two hues are, as the CIE76 ΔE*ab: the plain
 *    Euclidean distance in CIE Lab. It is the simple metric on purpose, so the
 *    number in this bundle's test and the number a product computes over its own
 *    additions are the same number, arrived at the same way. (CIEDE2000 is the
 *    better model of perception and a great deal more arithmetic; the threshold
 *    is set from the shipped set's own closest pair, so the simpler metric is
 *    calibrated rather than guessed.)
 * 2. **Contrast** — the WCAG 2.x contrast ratio, which is what decides whether a
 *    hue can be read against the ground it is drawn on.
 *
 * Values arrive in the spellings the configuration uses: `#RRGGBB` and
 * `rgb(r g b)`.
 */
final class ColorMetric
{
    /**
     * CIE76 ΔE*ab between two colours.
     */
    public static function distance(string $a, string $b): float
    {
        [$l1, $a1, $b1] = self::lab($a);
        [$l2, $a2, $b2] = self::lab($b);

        return sqrt(($l1 - $l2) ** 2 + ($a1 - $a2) ** 2 + ($b1 - $b2) ** 2);
    }

    /**
     * The WCAG contrast ratio, 1.0 (identical) to 21.0 (black on white).
     */
    public static function contrast(string $a, string $b): float
    {
        $first = self::luminance($a);
        $second = self::luminance($b);

        return (max($first, $second) + 0.05) / (min($first, $second) + 0.05);
    }

    /**
     * @return array{float, float, float} red, green, blue in 0..1
     */
    public static function rgb(string $color): array
    {
        $color = trim($color);

        if (1 === preg_match('/^#([0-9a-f]{6})$/i', $color, $hex)) {
            return [
                hexdec(substr($hex[1], 0, 2)) / 255,
                hexdec(substr($hex[1], 2, 2)) / 255,
                hexdec(substr($hex[1], 4, 2)) / 255,
            ];
        }

        if (1 === preg_match('/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/i', $color, $parts)) {
            return [(int) $parts[1] / 255, (int) $parts[2] / 255, (int) $parts[3] / 255];
        }

        throw new \InvalidArgumentException(\sprintf('"%s" is not a colour this check can measure: write #RRGGBB or rgb(r g b).', $color));
    }

    private static function luminance(string $color): float
    {
        [$r, $g, $b] = array_map(self::linear(...), self::rgb($color));

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @return array{float, float, float} L*, a*, b*
     */
    private static function lab(string $color): array
    {
        [$r, $g, $b] = array_map(self::linear(...), self::rgb($color));

        // sRGB → CIE XYZ (D65), then XYZ → Lab against the D65 white point.
        $x = (0.4124 * $r + 0.3576 * $g + 0.1805 * $b) / 0.95047;
        $y = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        $z = (0.0193 * $r + 0.1192 * $g + 0.9505 * $b) / 1.08883;

        $f = static fn (float $t): float => $t > 0.008856 ? $t ** (1 / 3) : 7.787 * $t + 16 / 116;

        return [116 * $f($y) - 16, 500 * ($f($x) - $f($y)), 200 * ($f($y) - $f($z))];
    }

    private static function linear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
