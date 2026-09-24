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

namespace Uhifadhi\Element\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * THE STYLE GUIDE, OVER HTTP.
 *
 * A component library is only as good as the page that shows it, so the guide
 * is a route and not a document — and a route can be asserted. This is also the
 * end-to-end proof that the bundle boots in a kernel that installs NOTHING of
 * the platform, which is the whole promise of the package.
 */
final class StyleGuideTest extends KernelTestCase
{
    public function testTheGuideRendersEveryComponentAndLinksBothStylesheets(): void
    {
        $html = $this->guide();

        // AssetMapper content-versions the filename, so the assertion is on the
        // path the bundle publishes rather than on the digest of the day.
        self::assertMatchesRegularExpression('#href="[^"]*bundles/uhifadhielement/element-tokens-[^"]+\.css"#', $html, 'A page with no other source of the house channels links the tokens first.');
        self::assertMatchesRegularExpression('#href="[^"]*bundles/uhifadhielement/element-[A-Za-z0-9_-]+\.css"#', $html);

        // The folding register, the flat one and the empty one.
        self::assertStringContainsString('<span class="tab">Positions<span class="src">· 3 · what each grants, and who holds it</span></span>', $html);
        self::assertStringContainsString('<span class="tab">Placements</span>', $html);
        self::assertStringContainsString('Nothing here yet.', $html);
    }

    public function testTheGuideOpensTheRowsTheAddressNames(): void
    {
        self::assertStringContainsString('<tr class="open" data-fold-id="ranger"', $this->guide('?open=ranger'));
        self::assertStringNotContainsString('<tr class="open"', $this->guide());
    }

    public function testTheFoldPanelSeesTheRowItBelongsTo(): void
    {
        $html = $this->guide();

        self::assertStringContainsString('<p>Warden — 3 seats, organization.</p>', $html);
        self::assertStringContainsString('<p>Ranger — 24 seats, area.</p>', $html);
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
