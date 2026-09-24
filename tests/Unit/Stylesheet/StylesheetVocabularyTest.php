<?php

declare(strict_types=1);

/*
 * This file is part of the UhifadhiLabs Element Module.
 *
 * (c) Ezekiel Mjema <https://github.com/eemjema>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uhifadhi\Element\Tests\Unit\Stylesheet;

use PHPUnit\Framework\TestCase;
use Uhifadhi\Element\UhifadhiElementBundle;

/**
 * EVERY CLASS A COMPONENT EMITS IS A CLASS THIS BUNDLE SHIPS.
 *
 * A class the sheet never defines does not fail — it falls back to the
 * browser's defaults, which looks fine in a suite that loads the design's own
 * stylesheet and broken in the product. This is the check that a page can only
 * ever be missing a rule on purpose.
 */
final class StylesheetVocabularyTest extends TestCase
{
    /**
     * The classes a component writes from PHP rather than from the template —
     * invisible to a scan of the markup, and just as able to fall back to the
     * browser's defaults.
     */
    private const array FROM_PHP = ['num', 'sorted'];

    public function testTheComponentTemplatesSpendNoClassTheSheetDoesNotDefine(): void
    {
        $css = self::built();

        foreach (self::classesEmitted() as $class => $template) {
            self::assertMatchesRegularExpression(
                '/\.'.preg_quote($class, '/').'\b/',
                $css,
                \sprintf('"%s" is emitted by %s and defined by no rule in the built stylesheet.', $class, $template),
            );
        }
    }

    public function testTheClassesTheComponentWritesFromPhpAreShippedToo(): void
    {
        $css = self::built();

        foreach (self::FROM_PHP as $class) {
            self::assertMatchesRegularExpression('/\.'.preg_quote($class, '/').'\b/', $css, $class.' is written by the component class and defined by no rule.');
        }
    }

    public function testTheVocabularyIsTheHouseRegisterVocabulary(): void
    {
        $classes = array_keys(self::classesEmitted());

        // The frame, the table, and the fold — the three halves of a register.
        foreach (['c', 'tab', 'src', 'tbl', 'fchev', 'foldrow', 'foldbox', 'fold-in'] as $expected) {
            self::assertContains($expected, $classes);
        }
    }

    /**
     * NO UTILITY SPELLING MAY NAME A COMPONENT CLASS. A framework class written
     * in Tailwind's own grammar is out-cascaded by the utility of the same name
     * — the `.w-12` lesson, where every dashboard cell came out 48px wide.
     */
    public function testNoComponentClassIsSpeltLikeAUtility(): void
    {
        foreach (array_keys(self::classesEmitted()) as $class) {
            self::assertDoesNotMatchRegularExpression(
                '/^(w|h|p|m|px|py|mx|my|text|bg|border|gap|flex|grid|col|row)-\d/',
                $class,
                $class.' collides with a Tailwind utility spelling; spell it out instead.',
            );
        }
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array<string, string> class => the template that emits it
     */
    private static function classesEmitted(): array
    {
        $found = [];

        foreach (glob(self::root().'/templates/components/*.html.twig') ?: [] as $path) {
            $template = file_get_contents($path);
            self::assertIsString($template);

            // Only the classes this bundle writes itself: the LITERAL head of a
            // class="…" attribute, so `class="foldrow{{ … }}"` is collected as
            // "foldrow" and a wholly computed attribute contributes nothing.
            // What a consumer passes through `attributes` is the consumer's
            // vocabulary and its own sheet's problem.
            preg_match_all('/class="([a-z0-9 _-]*)/i', $template, $literal);
            // …and the defaults a component hands to `attributes`, which is how
            // a component's own class reaches an element a consumer may add to.
            preg_match_all("/class:\s*'([a-z0-9 _-]+)'/i", $template, $defaults);

            foreach ([...$literal[1], ...$defaults[1]] as $attribute) {
                foreach (preg_split('/\s+/', trim($attribute)) ?: [] as $class) {
                    if ('' !== $class) {
                        $found[$class] = basename($path);
                    }
                }
            }
        }

        self::assertNotEmpty($found);

        return $found;
    }

    private static function built(): string
    {
        $css = file_get_contents(self::root().'/public/'.basename(UhifadhiElementBundle::STYLESHEET));
        self::assertIsString($css, 'The built stylesheet is committed: run "composer css:build".');

        return $css;
    }
}
