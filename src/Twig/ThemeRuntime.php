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

use Twig\Extension\RuntimeExtensionInterface;
use UtafitiLabs\ElementBundle\Theme\ThemeStyleService;

/**
 * What `element_theme()` runs, once it is genuinely called.
 *
 * A runtime holds the dependency; the extension holds only the name — which is
 * Twig's own way of keeping a page that never draws a component from building
 * the theme at all.
 *
 * @see https://symfony.com/doc/current/templating/twig_extension.html
 * @see vendor/symfony/twig-bundle/Resources/config/form.php — a core service
 *      carrying the `twig.runtime` tag by hand, exactly as this one does
 */
final class ThemeRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly ThemeStyleService $theme)
    {
    }

    public function theme(): string
    {
        return $this->theme->render();
    }
}
