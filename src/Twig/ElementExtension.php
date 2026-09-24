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

namespace UtafitiLabs\ElementBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `element_theme()` — the theme block, in the page's `<head>`.
 *
 * DECLARATION ONLY, so nothing is built to render a page that never calls it:
 * the function points at {@see ThemeRuntime}, which Twig instantiates the first
 * time it is actually called.
 *
 *   "That's why Twig allows decoupling the extension definition from its
 *    implementation." — https://symfony.com/doc/current/templating/twig_extension.html
 *
 * The same page documents the two tags a bundle that does not autoconfigure has
 * to apply by hand — `twig.extension` here and `twig.runtime` on the runtime,
 * both in config/services.php. The runtime tag is what
 * vendor/symfony/twig-bundle/DependencyInjection/Compiler/RuntimeLoaderPass.php
 * reads to build Twig's runtime locator, keyed by the runtime's CLASS.
 *
 * `is_safe: html` because the return value IS markup — a `<style>` element the
 * library writes itself, out of values the configuration tree has already
 * refused the characters that could close it with.
 */
final class ElementExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('element_theme', [ThemeRuntime::class, 'theme'], ['is_safe' => ['html']]),
        ];
    }
}
