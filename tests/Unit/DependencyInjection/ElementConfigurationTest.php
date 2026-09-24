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

namespace UtafitiLabs\ElementBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use UtafitiLabs\ElementBundle\DependencyInjection\ElementConfiguration;
use UtafitiLabs\ElementBundle\Tests\Support\ProcessedTheme;

/**
 * THE TREE IS THE PRODUCT'S ONLY DIFFERENCE FROM ANOTHER PRODUCT.
 *
 * Two applications install this bundle unchanged; what makes one of them look
 * like itself is what it writes under `element.theme`. So the tree is asserted
 * like an API: every channel exists, every one of them has a default, a partial
 * theme changes only what it states, and a value that could break out of the
 * `<style>` block is refused rather than escaped.
 */
#[CoversClass(ElementConfiguration::class)]
final class ElementConfigurationTest extends TestCase
{
    public function testAnInstallationThatWritesNothingGetsAWholeTheme(): void
    {
        $theme = ProcessedTheme::theme();

        self::assertSame(ElementConfiguration::DEFAULT_EMPTY_TEXT, ProcessedTheme::emptyText());
        self::assertSame('14px', $theme['radius']);
        self::assertSame('32px', $theme['control_height']);
        self::assertSame('rgb(15 138 104)', $theme['colors']['accent']);
        self::assertSame('rgb(62 217 168)', $theme['dark']['colors']['accent']);
        self::assertStringContainsString('system-ui', $theme['fonts']['ui']);
        self::assertStringContainsString('monospace', $theme['fonts']['mono']);
    }

    public function testEveryChannelTheLibraryRendersHasANodeInBothPalettes(): void
    {
        $theme = ProcessedTheme::theme();

        foreach (ElementConfiguration::channels() as $channel) {
            self::assertArrayHasKey($channel, $theme['colors'], $channel.' is rendered and cannot be configured.');
            self::assertArrayHasKey($channel, $theme['dark']['colors'], $channel.' has no dark value.');
        }
    }

    /**
     * A THEME STATES WHAT IT CHANGES. A product that wants its own accent says
     * one line and keeps every other channel of the library's.
     */
    public function testAPartialThemeOverridesOnlyWhatItStates(): void
    {
        $theme = ProcessedTheme::theme(['theme' => [
            'colors' => ['accent' => 'rebeccapurple'],
            'radius' => '4px',
        ]]);

        self::assertSame('rebeccapurple', $theme['colors']['accent']);
        self::assertSame('4px', $theme['radius']);
        self::assertSame('rgb(27 38 32)', $theme['colors']['ink'], 'An unstated channel keeps the library default.');
        self::assertSame('rgb(62 217 168)', $theme['dark']['colors']['accent'], 'Light and dark are stated separately.');
    }

    /**
     * THE HUES ARE AN OPEN SET (ruled 2026-09-24). The bundle ships them; a
     * product restates the ones it wants said differently and appends any it
     * needs, and neither gesture drops the rest.
     */
    public function testTheHuesShipAsASetAProductCanRestateAndExtend(): void
    {
        $shipped = ProcessedTheme::theme()['hues'];

        self::assertGreaterThanOrEqual(16, \count($shipped));
        self::assertSame('#1E7A46', $shipped['moss']);

        $config = ProcessedTheme::theme(['theme' => ['hues' => ['moss' => '#001100', 'seagrass' => '#004433']]]);

        self::assertSame('#001100', $config['hues']['moss']);
        self::assertSame('#004433', $config['hues']['seagrass']);
        self::assertSame($shipped['ochre'], $config['hues']['ochre'], 'Stating one hue keeps the rest.');
        self::assertSame(\count($shipped) + 1, \count($config['hues']));
    }

    public function testAHueNameThatIsNotACustomPropertyNameIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        ProcessedTheme::process(['theme' => ['hues' => ['Sea Grass' => '#004433']]]);
    }

    public function testAValueThatCouldCloseTheStyleBlockIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/refused/');

        ProcessedTheme::process(['theme' => ['colors' => ['accent' => 'red}</style><script>x</script>']]]);
    }

    public function testAnEmptyChannelIsRefusedRatherThanRenderedAsNothing(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        ProcessedTheme::process(['theme' => ['colors' => ['ground' => '']]]);
    }
}
