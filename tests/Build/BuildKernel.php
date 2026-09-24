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

namespace UtafitiLabs\ElementBundle\Tests\Build;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle;
use UtafitiLabs\ElementBundle\UtafitiLabsElementBundle;

/**
 * THE KERNEL THAT BUILDS THE STYLESHEET, and does nothing else.
 *
 * symfonycasts/tailwind-bundle's `tailwind:build` is a console command, and a
 * console command needs a kernel — but a library has no application to borrow
 * one from. So this is the smallest one that can hold the Tailwind bundle, and
 * it lives under autoload-dev, where it cannot reach an installation.
 *
 * `composer css:build` drives it. The OUTPUT is committed
 * (public/element.css): a reusable bundle cannot run an installation's asset
 * pipeline, so what it ships is a built file, and the build is the maintainer's
 * step rather than the consumer's.
 *
 * @see https://github.com/SymfonyCasts/tailwind-bundle
 */
final class BuildKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new SymfonycastsTailwindBundle();
        yield new UtafitiLabsElementBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'build',
            'http_method_override' => false,
            'handle_all_throwables' => true,
        ]);

        $container->extension('twig', ['strict_variables' => true]);

        // The library's own input file, and the Tailwind CLI version the built
        // stylesheet was made with — pinned, because "whatever is newest today"
        // is not a build anyone can reproduce.
        $container->extension('symfonycasts_tailwind', [
            'input_css' => '%kernel.project_dir%/assets/styles/element.css',
            'binary_version' => 'v4.3.3',
        ]);
    }
}
