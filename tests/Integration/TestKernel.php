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

namespace Uhifadhi\Element\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Uhifadhi\Element\Tests\Integration\Fixtures\StyleGuideController;
use Uhifadhi\Element\UhifadhiElementBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * The smallest INSTALLATION this library can live in, and every part of it is
 * real: framework + twig + the published TwigComponentBundle and StimulusBundle
 * this bundle's components are made of.
 *
 * NO DATABASE, because there is nothing to remember, and NO PLATFORM BUNDLE,
 * because there is nothing to borrow: a component library the platform is built
 * out of cannot be built out of the platform, and a kernel that quietly
 * installed the shell would hide the day this library stopped standing on its
 * own.
 *
 * It also mounts the STYLE GUIDE — the one page that renders every component
 * with sample rows, which is both what the functional suite reads and what a
 * maintainer opens in a browser.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        // The controllers this library publishes are resolved by the real
        // StimulusBundle, so the identifier in the markup is the identifier a
        // host would compute.
        yield new StimulusBundle();
        yield new TwigComponentBundle();
        yield new UhifadhiElementBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/element-module-tests/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/element-module-tests/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'router' => ['utf8' => true],
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            // asset() has to exist: the style guide links this bundle's
            // stylesheets with it, exactly as a host would.
            'assets' => true,
        ]);

        // A template that reads an undefined variable must FAIL here rather
        // than render a hole, because a component's props are its contract.
        $container->extension('twig', [
            'strict_variables' => true,
            'paths' => [__DIR__.'/Fixtures/templates' => 'Fixtures'],
        ]);

        $services = $container->services();

        // The style guide. A controller extends nothing and takes what it needs
        // explicitly, the way FrameworkBundle's own TemplateController does.
        $services->set('test.element.style_guide', StyleGuideController::class)
            ->args([service('twig')])
            ->public();
        $services->alias(StyleGuideController::class, 'test.element.style_guide')->public();

        // Private services the suite reaches for directly: without a reference
        // they would be removed at compile time.
        $services->alias('test.element.component_renderer', ComponentRendererInterface::class)->public();
        $services->alias('test.element.twig', 'twig')->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('element_style_guide', '/style-guide')
            ->controller([StyleGuideController::class, '__invoke'])
            ->methods(['GET']);
    }
}
