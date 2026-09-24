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

namespace UtafitiLabs\ElementBundle\Tests\Unit\Stylesheet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\Tests\Support\ProcessedTheme;
use UtafitiLabs\ElementBundle\UtafitiLabsElementBundle;

/**
 * THE FOLD CONTRACT, AS SHIPPED (ruled 2026-09-22).
 *
 * The contract is a set of declarations, and this bundle's built stylesheet is
 * what a page actually loads — so the assertion is made against the BUILT file,
 * not against the Tailwind input it was made from. A build that dropped, moved
 * or rewrote one of these lines would leave every register looking right in the
 * source and jumping open in the browser, which is exactly the failure a
 * contract that lives only in a document produces.
 *
 * Whitespace is collapsed before matching, because how a formatter lays a rule
 * out is not part of the contract; every value in it is.
 */
final class FoldContractTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function contract(): iterable
    {
        yield 'the track is a grid that measures 0fr while shut' => [
            'table.tbl tr.foldrow .foldbox { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .6s cubic-bezier(.4, 0, .2, 1); }',
        ];
        yield 'and 1fr open' => [
            'table.tbl tr.foldrow.open .foldbox { grid-template-rows: 1fr; }',
        ];
        yield 'the panel is clipped inside the track' => [
            'table.tbl tr.foldrow .fold-in { overflow: hidden; min-height: 0; }',
        ];
        yield "the panel's spacing is the content's margin, never padding on the track" => [
            'table.tbl tr.foldrow .fold-in > * { margin: 12px 0 16px; }',
        ];
        yield 'the chevron turns on the same curve' => [
            'transition: transform .6s cubic-bezier(.4, 0, .2, 1);',
        ];
        yield 'and a quarter turn when the row is open' => [
            'table.tbl tr.open .fchev { transform: rotate(90deg); }',
        ];
        yield 'a folding row draws no rule of its own' => [
            'table.tbl tr[data-fold-id] td { border-bottom: 0; }',
        ];
        // The fold row's own rule is asserted declaration by declaration:
        // Tailwind splits any color-mix() into a plain fallback plus an
        // @supports block, so the VALUES survive verbatim while the braces
        // around them do not.
        yield 'the fold row is inset under the chevron' => [
            'table.tbl tr.foldrow td { padding: 0 14px 0 36px;',
        ];
        yield 'and carries the dashed rule in both states' => [
            'border-bottom: 1px dashed color-mix(in srgb, var(--e-muted) 18%, transparent);',
        ];
        yield 'on its own quiet ground' => [
            'background: color-mix(in srgb, var(--e-muted) 4%, transparent);',
        ];
        yield 'the chevron column is 22px on both rows' => [
            'table.tbl .fchev-h { width: 22px; }',
        ];
        yield 'and the cell gives its right padding back' => [
            'table.tbl td.fchev-c { width: 22px; padding-right: 0; }',
        ];
    }

    #[DataProvider('contract')]
    public function testTheBuiltStylesheetShipsTheContractVerbatim(string $declaration): void
    {
        self::assertStringContainsString(
            self::collapse($declaration),
            self::collapse(self::builtStylesheet()),
            'The fold contract is ruled and ported verbatim: rebuild the stylesheet rather than restyling it (composer css:build).',
        );
    }

    /**
     * A SORTABLE HEADER IS A LINK, and the sorted column alone says so — the
     * other half of the register's vocabulary this bundle now ships.
     */
    public function testTheBuiltStylesheetShipsTheSortableHeaderVocabulary(): void
    {
        $css = self::collapse(self::builtStylesheet());

        foreach ([
            'table.tbl th a { color: inherit; font-weight: inherit; text-decoration: none; }',
            'table.tbl th.sorted { color: var(--e-ink); }',
            'table.tbl th.sorted::after { content: " \\25BE"; color: var(--e-accent); }',
            'table.tbl th.sorted[aria-sort="descending"]::after { content: " \\25B4"; }',
        ] as $declaration) {
            self::assertStringContainsString(self::collapse($declaration), $css);
        }
    }

    /**
     * THE COMPONENT SHEET DEFINES NO VALUE OF ITS OWN. A page that loaded a
     * second definition of `--e-accent` would get whichever sheet landed last,
     * which is a rendering decided by load order. The values live in ONE place —
     * the host's `element.theme` configuration, rendered by `element_theme()` —
     * and this sheet only ever spends them.
     */
    public function testTheComponentSheetSpendsTheChannelsAndDefinesNone(): void
    {
        $css = self::builtStylesheet();

        self::assertStringContainsString('var(--e-accent)', $css, 'The components are painted from the configured channels.');
        self::assertDoesNotMatchRegularExpression('/--e-[a-z-]+\s*:/', $css, 'Only the theme block defines a --e-* value.');
    }

    /**
     * AND THE THEME BLOCK DEFINES EVERY ONE OF THEM. A property the sheet spends
     * and the configuration never states paints with nothing at all, in silence.
     */
    public function testTheThemeBlockDefinesEveryPropertyTheComponentSheetSpends(): void
    {
        $theme = ProcessedTheme::service()->css();

        preg_match_all('/var\((--e-[a-z-]+)/', self::builtStylesheet(), $matches);
        self::assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $property) {
            self::assertStringContainsString($property.':', $theme, $property.' is spent by a component and defined by nobody.');
        }
    }

    private static function builtStylesheet(): string
    {
        $path = \dirname(__DIR__, 3).'/public/'.basename(UtafitiLabsElementBundle::STYLESHEET);
        self::assertFileExists($path, 'The built stylesheet is committed: run "composer css:build".');

        $css = file_get_contents($path);
        self::assertIsString($css);

        return $css;
    }

    private static function collapse(string $css): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $css));
    }
}
