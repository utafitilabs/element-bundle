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

namespace UtafitiLabs\ElementBundle\Tests\Unit\Asset;

use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\UtafitiLabsElementBundle;

/**
 * THE SEAM BETWEEN THE TEMPLATE AND THE SHIPPED SCRIPT.
 *
 * Every name that crosses the PHP↔JS boundary is asserted as a literal string
 * on both sides, because nothing that renders HTML can catch the two drifting
 * apart: a register whose chevron names a controller the manifest never
 * published looks perfect in every render test and does nothing in a browser.
 *
 * If a name here has to change, it changes in three places at once — the
 * bundle's constant, the manifest, and the class names the script reaches for —
 * which is the point.
 */
final class FoldControllerSeamTest extends TestCase
{
    public function testTheManifestPublishesTheControllerTheTemplateNames(): void
    {
        /** @var array{name?: string, symfony?: array{controllers?: array<string, array{main?: string, enabled?: bool}>}} $manifest */
        $manifest = json_decode(self::read('/assets/package.json'), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(UtafitiLabsElementBundle::ASSET_NAMESPACE, $manifest['name'] ?? null, 'StimulusBundle resolves a controller by "@".<package name>; the manifest name IS the asset namespace.');

        $controllers = $manifest['symfony']['controllers'] ?? [];
        self::assertArrayHasKey('register-fold', $controllers, 'The controller is published under the name the identifier is built from.');
        self::assertSame('controllers/register_fold_controller.js', $controllers['register-fold']['main'] ?? null);
        self::assertTrue($controllers['register-fold']['enabled'] ?? false, 'A component library whose fold never binds is a library that ships a broken component.');
    }

    /**
     * The identifier is StimulusBundle's own normalisation of the asset
     * namespace plus the controller name: "@" dropped, "/" and "_" to "-".
     */
    public function testThePublishedIdentifierIsTheNamespaceNormalised(): void
    {
        self::assertSame(
            'utafitilabs--element-bundle--register-fold',
            UtafitiLabsElementBundle::FOLD_CONTROLLER,
        );
        self::assertSame(
            str_replace(['@', '_', '/'], ['', '-', '--'], UtafitiLabsElementBundle::ASSET_NAMESPACE).'--register-fold',
            UtafitiLabsElementBundle::FOLD_CONTROLLER,
        );
    }

    public function testTheScriptReachesForTheClassesTheTemplateEmits(): void
    {
        $js = self::read('/assets/controllers/register_fold_controller.js');
        $template = self::read('/templates/components/Table.html.twig');

        // The class names cross as themselves; a data attribute crosses as the
        // dataset spelling the browser gives the script, which is why both
        // halves of the pair are named here rather than assumed equal.
        foreach (['foldrow' => 'foldrow', 'open' => 'open', 'data-fold-id' => 'foldId', 'data-fold-name' => 'foldName'] as $inMarkup => $inScript) {
            self::assertStringContainsString($inMarkup, $template, $inMarkup.' is read by the script and must be written by the template.');
            self::assertStringContainsString($inScript, $js, $inScript.' is written by the template as "'.$inMarkup.'" and must be read by the script.');
        }

        self::assertStringContainsString('static values = { param:', $js, 'The address parameter is a Stimulus value, so the server names it once.');
        self::assertStringContainsString('-param-value="', $template);
    }

    public function testTheFoldWritesTheOpenSetIntoTheAddressAndKeepsNoStateOfItsOwn(): void
    {
        $js = self::read('/assets/controllers/register_fold_controller.js');

        self::assertStringContainsString('window.history.replaceState', $js, 'The page a reader is looking at is the page they can send.');
        self::assertStringNotContainsString('localStorage', $js, 'The open set lives in the address, nowhere else.');
        self::assertStringNotContainsString('sessionStorage', $js);
    }

    /**
     * THE ASSETS DIRECTORY IS NEVER EMPTY. The bundle prepends it as an
     * AssetMapper path; GitHub zipballs drop empty directories, and a
     * --prefer-dist install would then 500 every page of the host.
     */
    public function testTheAssetsDirectoryShipsATrackedFile(): void
    {
        self::assertFileExists(self::root().'/assets/package.json');
    }

    private static function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents(self::root().$path);
        self::assertIsString($contents, $path.' must ship.');

        return $contents;
    }
}
