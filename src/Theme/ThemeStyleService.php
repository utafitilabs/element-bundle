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

namespace UtafitiLabs\ElementBundle\Theme;

use UtafitiLabs\ElementBundle\DependencyInjection\ElementConfiguration;

/**
 * THE THEME, AS ONE `<style>` BLOCK.
 *
 * The shipped stylesheet spends custom properties and defines none of them;
 * this service turns the resolved `element.theme` configuration into the
 * definitions, so a product themes the library by editing
 * `config/packages/element.yaml` and nothing else. There is no second
 * stylesheet to ship, override or load in the wrong order — the values are
 * values, resolved by the browser, and light and dark are two complete palettes
 * rather than a filter over one.
 *
 * The block is rendered ONCE, in `<head>`, BEFORE the bundle's stylesheet: a
 * custom property is inherited, not cascaded by source order, but a rule that
 * reads one the page has not yet defined paints with nothing at all.
 *
 * Values cannot close the block: `<`, `>`, `{`, `}` and `;` are refused by the
 * configuration tree at compile time (see {@see ElementConfiguration}), which is
 * a validation error an installation reads rather than an escape a reader never
 * sees.
 */
final class ThemeStyleService
{
    /**
     * @param array<string, string> $colors     channel => value, light
     * @param array<string, string> $darkColors channel => value, dark
     * @param array<string, string> $hues       hue name => value, light
     * @param array<string, string> $darkHues   hue name => value, dark
     * @param array<string, string> $fonts      "ui" and "mono"
     */
    public function __construct(
        private readonly array $colors,
        private readonly array $darkColors,
        private readonly array $hues,
        private readonly array $darkHues,
        private readonly array $fonts,
        private readonly string $shadow,
        private readonly string $darkShadow,
        private readonly string $radius,
        private readonly string $controlHeight,
    ) {
    }

    /**
     * The `<style>` element a page puts in its `<head>`.
     */
    public function render(): string
    {
        return "<style>\n".$this->css()."</style>\n";
    }

    /**
     * The declarations alone, for a deployment that would rather ship them as a
     * file of its own than inline them.
     */
    public function css(): string
    {
        $root = $this->declarations($this->colors, $this->shadow, $this->hues);

        // THE HUES, ALSO BY POSITION. The numbers are aliases of the named
        // values, so a deployment that restates a hue restates it once and both
        // spellings follow — pick by NAME where a meaning has to stay put, by
        // INDEX where a series just needs colours that differ.
        foreach (array_keys($this->hues) as $index => $name) {
            $root[] = $this->declaration('hue-'.($index + 1), \sprintf('var(%shue-%s)', ElementConfiguration::PREFIX, $name));
        }

        // Only in light does the type, the radius and the control height need
        // stating: dark changes the palette, never the measurements.
        $root[] = $this->declaration('font-ui', $this->fonts['ui'] ?? '');
        $root[] = $this->declaration('font-mono', $this->fonts['mono'] ?? '');
        $root[] = $this->declaration('radius', $this->radius);
        $root[] = $this->declaration('control-height', $this->controlHeight);

        return $this->rule(':root', $root)
            // `html.dark` is the contract: the class is put on <html> before the
            // first paint, so a page never flashes the other palette.
            .$this->rule('html.dark', $this->declarations($this->darkColors, $this->darkShadow, $this->darkHues));
    }

    /**
     * @param array<string, string> $colors
     * @param array<string, string> $hues
     *
     * @return list<string>
     */
    private function declarations(array $colors, string $shadow, array $hues): array
    {
        $declarations = [];

        // The channel order is the configuration's, so a block read in the
        // browser and the tree read in the documentation say the same thing in
        // the same sequence.
        foreach (ElementConfiguration::channels() as $channel) {
            $declarations[] = $this->declaration(str_replace('_', '-', $channel), $colors[$channel] ?? '');
        }

        $declarations[] = $this->declaration('shadow', $shadow);

        foreach ($hues as $name => $value) {
            $declarations[] = $this->declaration('hue-'.$name, $value);
        }

        return $declarations;
    }

    private function declaration(string $property, string $value): string
    {
        return \sprintf('    %s%s: %s;', ElementConfiguration::PREFIX, $property, $value);
    }

    /**
     * @param list<string> $declarations
     */
    private function rule(string $selector, array $declarations): string
    {
        return $selector." {\n".implode("\n", $declarations)."\n}\n";
    }
}
