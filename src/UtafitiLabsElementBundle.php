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

namespace UtafitiLabs\ElementBundle;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use UtafitiLabs\ElementBundle\DependencyInjection\ElementConfiguration;

/**
 * Element — a Twig component library.
 *
 * The vocabulary an application renders its screens with, as Twig components
 * styled with Tailwind, so a register drawn on one page and a register drawn on
 * another are the SAME OBJECT rather than two ports of one drawing. It depends
 * on no application package on purpose: a library an application is built out
 * of cannot be built out of the application.
 *
 * Zero-config: registering the bundle publishes the component namespace, so
 * `<twig:Element:Table>` resolves with nothing written in the installation, and
 * registers the assets directory under this bundle's AssetMapper namespace.
 */
final class UtafitiLabsElementBundle extends AbstractBundle
{
    /**
     * THE BUILT STYLESHEET, and the whole of what a page must link to render
     * this bundle's components. It is a BUILD OUTPUT — Tailwind's, from
     * assets/styles/element.css — committed rather than built by the
     * installation, because a reusable bundle cannot run an application's asset
     * pipeline and a stylesheet that needed one would not be a stylesheet.
     *
     * AssetMapper serves the bundle's public/ directory under
     * `bundles/<lowercased bundle name>/` with no configuration and no
     * assets:install.
     */
    public const string STYLESHEET = 'bundles/utafitilabselement/element.css';

    /**
     * THE PALETTE, FOR A PAGE THAT HAS NO OTHER SOURCE OF IT. The components
     * SPEND the theme channels (`rgb(var(--c-acc))`) and define none, so a page
     * that already has them links {@see self::STYLESHEET} alone and nothing is
     * defined twice. A page standing on its own links this first.
     */
    public const string TOKENS_STYLESHEET = 'bundles/utafitilabselement/element-tokens.css';

    /**
     * Flex keys `assets/controllers.json` by `'@'.<composer package name>` and
     * StimulusBundle resolves that key back to this directory, so the two
     * spellings cannot be chosen independently.
     */
    public const string ASSET_NAMESPACE = '@utafitilabs/element-bundle';

    /** StimulusBundle's own normalisation of the namespace: '@' dropped, '/' and '_' to '-'. */
    public const string CONTROLLER_PREFIX = 'utafitilabs--element-bundle--';

    /**
     * The identifier the register's chevron names. Stated once because it has
     * three readers that must never disagree: the component template that
     * writes `data-controller`, assets/package.json that publishes the
     * controller, and the script itself.
     */
    public const string FOLD_CONTROLLER = self::CONTROLLER_PREFIX.'register-fold';

    /** The PHP namespace every component of this library lives in. */
    public const string COMPONENT_NAMESPACE = 'UtafitiLabs\\ElementBundle\\Twig\\Components\\';

    /** What a component is called from a template: `<twig:Element:Table>`. */
    public const string COMPONENT_PREFIX = 'Element';

    /** Where this bundle's component templates live, in Twig's own spelling. */
    public const string TEMPLATE_DIRECTORY = '@UtafitiLabsElement/components';

    /** Config lives under "element:", not the class-derived "utafiti_labs_element:". */
    protected string $extensionAlias = 'element';

    public function configure(DefinitionConfigurator $definition): void
    {
        ElementConfiguration::define($definition->rootNode());
    }

    /**
     * What an installation must never have to write.
     *
     * Every prepend is guarded on the extension existing — a kernel without
     * AssetMapper or without TwigComponentBundle must still boot — and passes
     * `prepend: true`, because `extension()` APPENDS by default even here,
     * which would put this bundle's opinion LAST, where it overrules the
     * application instead of deferring to it.
     *
     * Patterned on symfony/ux-turbo's TurboExtension::prepend() (AssetMapper
     * path) and on the shape TwigComponentExtension's own configuration tree
     * requires (vendor/symfony/ux-twig-component/src/DependencyInjection/TwigComponentExtension.php:
     * `defaults` is isRequired(), keyed by a namespace that must end in "\").
     *
     * @see https://symfony.com/doc/current/frontend/asset_mapper.html
     * @see https://symfony.com/bundles/ux-twig-component/current/index.html#registering-a-third-party-component-namespace
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Stimulus/ESM assets under this bundle's AssetMapper namespace. Both
        // conditions are real: a kernel may have no framework extension, and
        // AssetMapper is an optional component of the ones it does have.
        if ($builder->hasExtension('framework') && interface_exists(AssetMapperInterface::class)) {
            $container->extension('framework', ['asset_mapper' => ['paths' => [
                \dirname(__DIR__).'/assets' => self::ASSET_NAMESPACE,
            ]]], prepend: true);
        }

        // The component namespace, so `<twig:Element:Table>` resolves in an
        // installation that wrote nothing. The map is keyed by namespace, so an
        // application adding its own components adds a key and overrides
        // nothing of this bundle's.
        if ($builder->hasExtension('twig_component')) {
            $container->extension('twig_component', [
                'defaults' => [
                    self::COMPONENT_NAMESPACE => [
                        'template_directory' => self::TEMPLATE_DIRECTORY,
                        'name_prefix' => self::COMPONENT_PREFIX,
                    ],
                ],
                // Both `defaults` and `anonymous_template_directory` are
                // isRequired() in TwigComponentBundle's tree, so a kernel that
                // installs this library and writes no twig_component config
                // would refuse to boot. The value is the one that extension's
                // own info() names as the default, and prepend: true leaves an
                // application free to say something else.
                'anonymous_template_directory' => 'components',
            ], prepend: true);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $table = \is_array($config['table'] ?? null) ? $config['table'] : [];

        $builder->setParameter(
            'element.table.empty_text',
            \is_string($table['empty_text'] ?? null) ? $table['empty_text'] : ElementConfiguration::DEFAULT_EMPTY_TEXT,
        );

        $container->import('../config/services.php');
    }
}
