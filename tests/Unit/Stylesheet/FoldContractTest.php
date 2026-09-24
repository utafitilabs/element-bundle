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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Uhifadhi\Element\UhifadhiElementBundle;

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
        yield 'and carries the house dashed rule in both states' => [
            'border-bottom: 1px dashed color-mix(in srgb, rgb(var(--c-fog)) 18%, transparent);',
        ];
        yield 'on its own quiet ground' => [
            'background: color-mix(in srgb, rgb(var(--c-fog)) 4%, transparent);',
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
            'table.tbl th.sorted { color: rgb(var(--c-tx)); }',
            'table.tbl th.sorted::after { content: " \\25BE"; color: rgb(var(--c-acc)); }',
            'table.tbl th.sorted[aria-sort="descending"]::after { content: " \\25B4"; }',
        ] as $declaration) {
            self::assertStringContainsString(self::collapse($declaration), $css);
        }
    }

    /**
     * THE PALETTE IS NOT REDEFINED BY THE COMPONENT SHEET. A page that loads a
     * second definition of `--c-acc` gets whichever sheet landed last, which is
     * a rendering decided by load order. The channels live in ONE file
     * (element-tokens.css, for a page with no other source of them) and the
     * component sheet only ever spends them.
     */
    public function testTheComponentSheetSpendsTheChannelsAndDefinesNone(): void
    {
        $css = self::builtStylesheet();

        self::assertStringContainsString('var(--c-acc)', $css, 'The components are painted from the house channels.');
        self::assertDoesNotMatchRegularExpression('/--c-[a-zA-Z0-9]+\s*:/', $css, 'Only the token sheet defines a --c-* channel.');
    }

    public function testTheTokenSheetDefinesEveryChannelTheComponentSheetSpends(): void
    {
        $tokens = file_get_contents(\dirname(__DIR__, 3).'/public/'.basename(UhifadhiElementBundle::TOKENS_STYLESHEET));
        self::assertIsString($tokens);

        preg_match_all('/var\((--c-[a-zA-Z0-9]+)/', self::builtStylesheet(), $matches);
        self::assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $channel) {
            self::assertMatchesRegularExpression('/'.preg_quote($channel, '/').'\s*:/', $tokens, $channel.' is spent by a component and defined by nobody.');
        }
    }

    private static function builtStylesheet(): string
    {
        $path = \dirname(__DIR__, 3).'/public/'.basename(UhifadhiElementBundle::STYLESHEET);
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
