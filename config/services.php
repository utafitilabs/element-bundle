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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Uhifadhi\Element\Twig\Components\Table;

/*
 * EXPLICIT DI, AS A REUSABLE BUNDLE MUST.
 *
 *   "Services should not use autowiring or autoconfiguration. Instead, all
 *    services should be defined explicitly." … "If the bundle defines services,
 *    they must be prefixed with the bundle alias instead of using fully
 *    qualified class names."
 *   — https://symfony.com/doc/current/bundles/best_practices.html
 *
 * WHICH IS WHY THE COMPONENT CARRIES NO #[AsTwigComponent]. That attribute is
 * read by an autoconfiguration callback registered in
 * vendor/symfony/ux-twig-component/src/DependencyInjection/TwigComponentExtension.php,
 * and autoconfiguration never fires for a bundle's own services — so the
 * `twig.component` tag is applied here by hand, carrying exactly what
 * AsTwigComponent::serviceConfig() would have put on it: the component's `key`
 * and its `template`.
 *
 * Both are stated even though the namespace this bundle prepends would derive
 * them (TwigComponentPass::calculateTemplate), so the component still resolves
 * in a host that has overruled the `twig_component.defaults` map.
 *
 * PHP, not YAML: a reusable bundle must not force symfony/yaml on an
 * installation, and an FQCN reference here stays refactor-safe and analysable.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()

        // The house register. TwigComponentPass marks every tagged component
        // not-shared, so one page may draw as many tables as it likes.
        ->set('element.twig.component.table', Table::class)
            ->args([param('element.table.empty_text')])
            ->tag('twig.component', [
                'key' => 'Element:Table',
                'template' => '@UhifadhiElement/components/Table.html.twig',
                // NOT a detail: ComponentMetadata::expose_public_props() defaults
                // to FALSE when the key is absent, and the autoconfiguration
                // callback array_filter()s the attribute's config — so the
                // attribute's own `true` is simply never written down. A bundle
                // tagging by hand has to say it, or every prop of every
                // component is invisible to its template.
                'expose_public_props' => true,
            ])
    ;
};
