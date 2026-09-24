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

namespace UtafitiLabs\ElementBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * THE STYLE GUIDE, OVER HTTP.
 *
 * A component library is only as good as the page that shows it, so the guide
 * is a route and not a document — and a route can be asserted. This is also the
 * end-to-end proof that the bundle boots in a kernel that installs NOTHING
 * beyond Symfony itself, which is the whole promise of the package.
 */
final class StyleGuideTest extends KernelTestCase
{
    public function testTheGuideRendersEveryComponentAndLinksBothStylesheets(): void
    {
        $html = $this->guide();

        // AssetMapper content-versions the filename, so the assertion is on the
        // path the bundle publishes rather than on the digest of the day.
        self::assertMatchesRegularExpression('#href="[^"]*bundles/utafitilabselement/element-tokens-[^"]+\.css"#', $html, 'A page with no other source of the theme channels links the tokens first.');
        self::assertMatchesRegularExpression('#href="[^"]*bundles/utafitilabselement/element-[A-Za-z0-9_-]+\.css"#', $html);

        // The folding register, the flat one and the empty one.
        self::assertStringContainsString('<span class="tab">Berths<span class="src">· 3 · how deep it is, and what is moored there</span></span>', $html);
        self::assertStringContainsString('<span class="tab">Quays</span>', $html);
        self::assertStringContainsString('Nothing here yet.', $html);
    }

    public function testTheGuideOpensTheRowsTheAddressNames(): void
    {
        self::assertStringContainsString('<tr class="open" data-fold-id="east-7"', $this->guide('?open=east-7'));
        self::assertStringNotContainsString('<tr class="open"', $this->guide());
    }

    public function testTheFoldPanelSeesTheRowItBelongsTo(): void
    {
        $html = $this->guide();

        self::assertStringContainsString('<p>North 12 — 7 m at north quay.</p>', $html);
        self::assertStringContainsString('<p>East 7 — 4 m at fish dock.</p>', $html);
    }

    private function guide(string $query = ''): string
    {
        $kernel = self::bootKernel();
        $response = $kernel->handle(Request::create('/style-guide'.$query), HttpKernelInterface::MAIN_REQUEST, false);

        self::assertSame(200, $response->getStatusCode());
        self::ensureKernelShutdown();

        return (string) $response->getContent();
    }
}
