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
 * Static so the tree is unit-testable with a plain Processor and shared
 * verbatim by the bundle's configure() — one definition, two readers.
 *
 * @see https://symfony.com/doc/current/bundles/configuration.html
 */
final class ElementConfiguration
{
    /**
     * WHAT AN EMPTY REGISTER SAYS. Copy, therefore an installation's — a
     * deployment that calls its registers something else says so here instead
     * of overriding a template.
     */
    public const string DEFAULT_EMPTY_TEXT = 'Nothing here yet.';

    public static function define(NodeDefinition $root): void
    {
        \assert($root instanceof ArrayNodeDefinition);

        $root
            ->children()
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
}
