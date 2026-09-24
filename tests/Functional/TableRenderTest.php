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

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Uhifadhi\Element\Tests\Integration\TestKernel;
use Uhifadhi\Element\UhifadhiElementBundle;

/**
 * What `<twig:Element:Table>` actually puts on the page, rendered through a real
 * kernel with the real TwigComponentBundle — because the fold contract is a
 * contract about MARKUP, and a contract about markup is only kept by markup.
 */
final class TableRenderTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testTheFrameIsTheHouseCardAndTheTabNamesAndCountsTheTable(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'note' => 'what each grants, and who holds it',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'Warden']], ['cells' => ['name' => 'Scout']]],
        ]);

        self::assertStringContainsString('<div class="c el-reg"', $html);
        self::assertStringContainsString('<span class="tab">Positions<span class="src">· 2 · what each grants, and who holds it</span></span>', $html);
        self::assertStringContainsString('<table class="tbl"', $html);
    }

    public function testAColumnIsAHeaderCellAndACellPerRow(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [
                ['key' => 'name', 'label' => 'Position'],
                ['key' => 'seats', 'label' => 'Seats', 'numeric' => true],
            ],
            'rows' => [['cells' => ['name' => 'Warden', 'seats' => '4']]],
        ]);

        self::assertStringContainsString('<th>Position</th>', $html);
        self::assertStringContainsString('<th class="num">Seats</th>', $html);
        self::assertStringContainsString('<td>Warden</td>', $html);
        self::assertStringContainsString('<td class="num">4</td>', $html);
    }

    public function testACellIsEscapedBecauseAPositionNameIsData(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => '<script>x</script>']]],
        ]);

        self::assertStringNotContainsString('<script>x</script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * A SORTABLE HEADER IS A LINK, and the sorted column alone says so — with
     * `aria-sort` for a reader who cannot see the mark the sheet draws.
     */
    public function testTheSortedColumnIsMarkedOnceAndAnnouncedItsDirection(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'sort' => 'seats',
            'direction' => 'descending',
            'columns' => [
                ['key' => 'name', 'label' => 'Position', 'sortUrl' => '?sort=name'],
                ['key' => 'seats', 'label' => 'Seats', 'sortUrl' => '?sort=seats'],
            ],
            'rows' => [],
        ]);

        self::assertStringContainsString('<th><a href="?sort=name">Position</a></th>', $html);
        self::assertStringContainsString('<th class="sorted" aria-sort="descending"><a href="?sort=seats">Seats</a></th>', $html);
        self::assertSame(1, substr_count($html, 'aria-sort'));
    }

    /**
     * THE FOLD CONTRACT, AS MARKUP (ruled 2026-09-22). A folding row carries a
     * chevron cell and its identity; ONE `tr.foldrow` follows it holding
     * `.foldbox > .fold-in > content`.
     */
    public function testAFoldingRowIsFollowedByExactlyOneFoldRow(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position'], ['key' => 'seats', 'label' => 'Seats']],
            'rows' => [
                ['id' => 'warden', 'name' => 'Warden', 'foldable' => true, 'cells' => ['name' => 'Warden']],
                ['id' => 'scout', 'name' => 'Scout', 'foldable' => true, 'cells' => ['name' => 'Scout']],
            ],
        ]);

        self::assertStringContainsString('<th class="fchev-h"></th>', $html);
        self::assertStringContainsString('<td class="fchev-c">', $html);
        self::assertStringContainsString('data-fold-id="warden"', $html);
        self::assertStringContainsString('data-fold-name="Warden"', $html);
        self::assertSame(2, substr_count($html, 'class="foldrow"'));
        self::assertSame(2, substr_count($html, '<div class="foldbox"><div class="fold-in">'));
        // Three columns: the chevron the component owns plus the caller's two.
        self::assertSame(2, substr_count($html, '<td colspan="3">'));
    }

    public function testTheServerRendersARowOpenWhenTheAddressNamesIt(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'open' => 'warden',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [
                ['id' => 'warden', 'name' => 'Warden', 'foldable' => true, 'cells' => ['name' => 'Warden']],
                ['id' => 'scout', 'name' => 'Scout', 'foldable' => true, 'cells' => ['name' => 'Scout']],
            ],
        ]);

        self::assertStringContainsString('<tr class="open" data-fold-id="warden" data-fold-name="Warden">', $html);
        self::assertStringContainsString('<tr class="foldrow open">', $html);
        self::assertStringContainsString('<tr data-fold-id="scout" data-fold-name="Scout">', $html);
        self::assertSame(1, substr_count($html, 'aria-expanded="true"'));
        self::assertSame(1, substr_count($html, 'aria-expanded="false"'));
        self::assertStringContainsString('aria-label="Collapse Warden"', $html);
        self::assertStringContainsString('aria-label="Expand Scout"', $html);
    }

    /**
     * THE CHEVRON'S CLICK IS THE CONTROLLER'S, and the identifier it names is
     * the one the manifest publishes — the JS↔template seam of this bundle.
     */
    public function testTheTableWiresTheFoldControllerByItsPublishedIdentifier(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['id' => 'warden', 'name' => 'Warden', 'foldable' => true, 'cells' => ['name' => 'Warden']]],
        ]);

        $controller = UhifadhiElementBundle::FOLD_CONTROLLER;

        self::assertStringContainsString('data-controller="'.$controller.'"', $html);
        self::assertStringContainsString('data-action="'.$controller.'#toggle"', $html);
        self::assertStringContainsString('data-'.$controller.'-param-value="open"', $html);
    }

    public function testATableThatFoldsNothingGrowsNoChevronColumn(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'Warden']]],
        ]);

        self::assertStringNotContainsString('fchev', $html);
        self::assertStringNotContainsString('foldrow', $html);
        self::assertStringNotContainsString('data-controller', $html);
    }

    public function testAnEmptyRegisterSaysSoInsteadOfDrawingNothing(): void
    {
        $html = $this->render([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [],
        ]);

        self::assertStringContainsString('<td class="el-none" colspan="1">Nothing here yet.</td>', $html);
    }

    /**
     * THE THREE SLOTS. `filters` is the bar above the table, `row` the cells of
     * one row, `fold` what opens underneath it — and `row` and `fold` both see
     * the row being drawn, which is the whole reason they are blocks and not
     * props.
     */
    public function testTheCallerOwnsTheCellsTheFoldPanelAndTheFilterBar(): void
    {
        $html = $this->renderSource(<<<'TWIG'
            <twig:Element:Table title="Positions" :columns="columns" :rows="rows">
                <twig:block name="filters"><div class="lfilt">a filter bar</div></twig:block>
                <twig:block name="row"><td><b>{{ row.cell('name') }}</b></td></twig:block>
                <twig:block name="fold"><p>seats under {{ row.name }}</p></twig:block>
            </twig:Element:Table>
            TWIG, [
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['id' => 'warden', 'name' => 'Warden', 'foldable' => true, 'cells' => ['name' => 'Warden']]],
        ]);

        self::assertStringContainsString('<div class="lfilt">a filter bar</div>', $html);
        self::assertStringContainsString('<td><b>Warden</b></td>', $html);
        self::assertStringContainsString('<div class="fold-in"><p>seats under Warden</p></div>', $html);
    }

    public function testAConsumersOwnAttributesLandOnTheCard(): void
    {
        $html = $this->renderSource(
            '<twig:Element:Table title="Positions" :columns="columns" :rows="[]" id="positions" class="preg" />',
            ['columns' => [['key' => 'name', 'label' => 'Position']]],
        );

        self::assertStringContainsString('<div class="c el-reg preg" id="positions">', $html);
    }

    /** @param array<string, mixed> $props */
    private function render(array $props): string
    {
        /** @var ComponentRendererInterface $renderer */
        $renderer = $this->kernel->getContainer()->get('test.element.component_renderer');

        return $renderer->createAndRender('Element:Table', $props);
    }

    /** @param array<string, mixed> $context */
    private function renderSource(string $source, array $context): string
    {
        /** @var \Twig\Environment $twig */
        $twig = $this->kernel->getContainer()->get('test.element.twig');

        return $twig->createTemplate($source)->render($context);
    }
}
