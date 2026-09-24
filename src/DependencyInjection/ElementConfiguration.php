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

namespace UtafitiLabs\ElementBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * The `element:` configuration tree.
 *
 * WHERE THE FLEXIBILITY LIVES. The shipped stylesheet paints with custom
 * properties and decides not one value; every colour, both type stacks, the
 * corner radius and the control height are stated here and rendered into the
 * page by {@see \UtafitiLabs\ElementBundle\Theme\ThemeStyleService}. Two
 * products that install this bundle unchanged therefore differ by colour and by
 * type, and by nothing else — which is the whole reason the theme is
 * configuration rather than a second stylesheet.
 *
 * Static so the tree is unit-testable with a plain Processor and shared
 * verbatim by the bundle's configure() — one definition, two readers.
 *
 * @see https://symfony.com/doc/current/bundles/configuration.html
 *      "In bundles extending the AbstractBundle class, you can add all the
 *       logic related to processing the configuration in that class"
 * @see vendor/symfony/http-kernel/Bundle/AbstractBundle.php — configure() takes
 *      the DefinitionConfigurator whose rootNode() this method fills
 */
final class ElementConfiguration
{
    /**
     * WHAT AN EMPTY REGISTER SAYS. Copy, therefore the application's — a
     * deployment that calls its registers something else says so here instead
     * of overriding a template.
     */
    public const string DEFAULT_EMPTY_TEXT = 'Nothing here yet.';

    /**
     * The custom-property prefix every value in this tree is rendered under, so
     * a page that also carries somebody else's design tokens cannot collide
     * with this library's.
     */
    public const string PREFIX = '--e-';

    /**
     * THE CHANNELS, IN THE ORDER THEY ARE RENDERED. Product-neutral names: a
     * ground to stand on, two surfaces, three weights of ink, two rules, an
     * accent and the four states. A component may spend any of them; none of
     * them names a feature, a place or a customer.
     *
     * @var array<string, array{0: string, 1: string, 2: string}> channel => [light, dark, what it is]
     */
    private const array COLORS = [
        'ground' => ['rgb(243 242 235)', 'rgb(12 19 16)', 'the page behind everything'],
        'surface' => ['rgb(255 255 255)', 'rgb(21 32 25)', 'a card, at the top of its gradient'],
        'surface_low' => ['rgb(251 250 246)', 'rgb(17 26 20)', 'the same card, at the bottom of it'],
        'raised' => ['rgb(236 235 226)', 'rgb(28 41 33)', 'chips, fields, anything on a card'],
        'ink' => ['rgb(27 38 32)', 'rgb(234 242 236)', 'primary text'],
        'muted' => ['rgb(87 100 90)', 'rgb(150 168 156)', 'secondary text, and the rules mixed from it'],
        'faint' => ['rgb(118 132 122)', 'rgb(124 140 129)', 'tertiary text'],
        'accent' => ['rgb(15 138 104)', 'rgb(62 217 168)', 'the one accent: live state and calls to action'],
        'accent_ink' => ['rgb(255 255 255)', 'rgb(12 19 16)', 'text that survives on the accent'],
        'ok' => ['rgb(30 122 70)', 'rgb(99 201 127)', 'went well'],
        'warn' => ['rgb(154 107 20)', 'rgb(219 163 63)', 'worth a look'],
        'danger' => ['rgb(186 66 39)', 'rgb(224 91 65)', 'went wrong'],
        'danger_ink' => ['rgb(255 255 255)', 'rgb(255 255 255)', 'text that survives on the danger fill'],
        'critical' => ['rgb(143 29 14)', 'rgb(255 74 56)', 'still going wrong, and wants somebody now'],
        'line' => ['rgba(23, 38, 30, 0.11)', 'rgba(216, 236, 224, 0.09)', 'a border'],
        'line_strong' => ['rgba(23, 38, 30, 0.20)', 'rgba(216, 236, 224, 0.17)', 'a border that has to be seen'],
    ];

    /**
     * THE CATEGORICAL HUES (ruled 2026-09-24). A set of colours for telling
     * CATEGORIES apart, NAMED BY WHAT THEY LOOK LIKE, because what they MEAN is
     * the consuming product's: a product maps its own categories onto them, by
     * name where a meaning must stay put or by index where a series just needs
     * colours, and this library never learns what a category is.
     *
     * Eighteen, and the count is a measurement rather than a preference: each
     * one is at least {@see self::HUE_DISTANCE} apart from every other in CIE76
     * Lab distance and clears {@see self::HUE_CONTRAST} against the ground it is
     * drawn on, in light and in dark alike — asserted, as the set grows, by
     * tests/Unit/Theme/HuePaletteTest.php. The first nine are the older set and
     * keep their positions, so a mapping written against them survives.
     *
     * @var array<string, array{0: string, 1: string}> name => [on a light ground, on a dark one]
     */
    private const array HUES = [
        'moss' => ['#1E7A46', '#4FA86A'],
        'ochre' => ['#8A6410', '#EFD27E'],
        'sky' => ['#15678F', '#5FB0E8'],
        'rose' => ['#A83A52', '#E8899B'],
        'plum' => ['#6A3FA8', '#B68BE8'],
        'indigo' => ['#3F3FB5', '#8A87E8'],
        'rust' => ['#94500D', '#E0742E'],
        'magenta' => ['#8E2C70', '#DA7CB6'],
        'slate' => ['#4C5C66', '#9DAAB4'],
        'olive' => ['#5D622F', '#A7A981'],
        'mauve' => ['#70518D', '#B69CCB'],
        'clay' => ['#894F3F', '#CB9B8C'],
        'brick' => ['#AB341F', '#ED8C74'],
        'teal' => ['#096B5A', '#78B1A2'],
        'fern' => ['#376A0C', '#8DB06D'],
        'cobalt' => ['#265BB4', '#91A3E9'],
        'mulberry' => ['#894B64', '#CA98AA'],
        'orchid' => ['#903A9B', '#D08FD6'],
    ];

    /**
     * THE FLOOR TWO HUES MUST STAND APART BY, as a CIE76 Lab distance — the
     * plain Euclidean one, documented rather than clever, so the check reads the
     * same in this bundle's tests as in a product's. The shipped set's closest
     * pair measures 13.3.
     */
    public const float HUE_DISTANCE = 12.0;

    /** And the floor a hue clears against the ground it is drawn on. */
    public const float HUE_CONTRAST = 4.5;

    /** A hue name becomes a custom property, so it is spelt like one. */
    private const string HUE_NAME = '/^[a-z][a-z0-9-]*$/';

    private const string DEFAULT_SHADOW = '0 6px 22px rgba(23, 38, 30, 0.10)';
    private const string DEFAULT_DARK_SHADOW = '0 8px 26px rgba(0, 0, 0, 0.34)';
    private const string DEFAULT_FONT_UI = '-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif';
    private const string DEFAULT_FONT_MONO = 'ui-monospace, SFMono-Regular, Menlo, monospace';
    private const string DEFAULT_RADIUS = '14px';
    private const string DEFAULT_CONTROL_HEIGHT = '32px';

    /**
     * A VALUE GOES INTO A `<style>` BLOCK, so a value that could close one is
     * refused at compile time rather than escaped at render time. Nothing a
     * legitimate colour, length or font stack contains is excluded.
     */
    private const string SAFE_VALUE = '/^[^<>{};]+$/';

    private const string REFUSED = 'A theme value is written into a <style> block, so "<", ">", "{", "}" and ";" are refused: %s';

    /**
     * @return list<string> the channel names, in render order
     */
    public static function channels(): array
    {
        return array_keys(self::COLORS);
    }

    /**
     * @return array<string, string> the shipped hues, name => value, in the order they are numbered
     */
    public static function hues(int $variant = 0): array
    {
        return array_map(static fn (array $pair): string => $pair[$variant], self::HUES);
    }

    public static function define(NodeDefinition $root): void
    {
        \assert($root instanceof ArrayNodeDefinition);

        $root
            ->children()
                ->arrayNode('theme')
                    ->info('Every value the shipped stylesheet paints with. Rendered into the page by element_theme().')
                    ->addDefaultsIfNotSet()
                    ->append(self::colors('colors', 0))
                    ->append(self::hueNodes(0))
                    ->children()
                        ->scalarNode('shadow')
                            ->info('The drop shadow a card carries.')
                            ->defaultValue(self::DEFAULT_SHADOW)
                            ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                        ->end()
                        ->arrayNode('fonts')
                            ->info('Two stacks: everything is set in one of them.')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('ui')
                                    ->info('Body and headings.')
                                    ->defaultValue(self::DEFAULT_FONT_UI)
                                    ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                                ->end()
                                ->scalarNode('mono')
                                    ->info('Tabs, table headers and figures.')
                                    ->defaultValue(self::DEFAULT_FONT_MONO)
                                    ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('radius')
                            ->info('The corner radius of a card.')
                            ->defaultValue(self::DEFAULT_RADIUS)
                            ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                        ->end()
                        ->scalarNode('control_height')
                            ->info('The height every control in a component row is built to.')
                            ->defaultValue(self::DEFAULT_CONTROL_HEIGHT)
                            ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                        ->end()
                        ->arrayNode('dark')
                            ->info('The same values again for a page marked <html class="dark">. Light and dark are two complete palettes, not a filter over one.')
                            ->addDefaultsIfNotSet()
                            ->append(self::colors('colors', 1))
                            ->append(self::hueNodes(1))
                            ->children()
                                ->scalarNode('shadow')
                                    ->defaultValue(self::DEFAULT_DARK_SHADOW)
                                    ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('table')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('empty_text')
                            ->info('What a register says when it has no rows.')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_EMPTY_TEXT)
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * One palette, defaulted from column $variant of {@see self::COLORS}.
     */
    private static function colors(string $name, int $variant): ArrayNodeDefinition
    {
        $colors = (new ArrayNodeDefinition($name))
            ->info('The palette channels. Every one has a default, so a theme states only what it changes.')
            ->addDefaultsIfNotSet();

        $children = $colors->children();

        foreach (self::COLORS as $channel => [$light, $dark, $what]) {
            $children
                ->scalarNode($channel)
                    ->info($what)
                    ->cannotBeEmpty()
                    ->defaultValue(0 === $variant ? $light : $dark)
                    ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
                ->end();
        }

        $children->end();

        return $colors;
    }

    /**
     * The hues, as an OPEN map defaulted to the shipped set: a product may
     * restate any value and may append hues of its own, and the two are the
     * same gesture. `beforeNormalization` is what makes that true — a
     * prototyped node otherwise REPLACES its default wholesale, so stating one
     * hue would silently drop the other seventeen.
     */
    private static function hueNodes(int $variant): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('hues');
        $node
            ->info('Colours for telling categories apart, named by look. Override any of them, or append your own; the index form follows this order.')
            ->useAttributeAsKey('name')
            ->normalizeKeys(false)
            ->defaultValue(self::hues($variant))
            ->beforeNormalization()
                ->ifArray()
                ->then(static fn (array $hues): array => array_merge(self::hues($variant), $hues))
            ->end()
            ->validate()
                ->ifTrue(static function (array $hues): bool {
                    foreach (array_keys($hues) as $name) {
                        if (!\is_string($name) || 1 !== preg_match(self::HUE_NAME, $name)) {
                            return true;
                        }
                    }

                    return false;
                })
                ->thenInvalid('A hue name is rendered as --e-hue-<name>, so it is lowercase letters, digits and hyphens: %s')
            ->end()
            ->scalarPrototype()
                ->cannotBeEmpty()
                ->validate()->ifTrue(self::unsafe(...))->thenInvalid(self::REFUSED)->end()
            ->end();

        return $node;
    }

    private static function unsafe(mixed $value): bool
    {
        return !\is_string($value) || 1 !== preg_match(self::SAFE_VALUE, $value);
    }
}
