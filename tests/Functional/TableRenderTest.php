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

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use UtafitiLabs\ElementBundle\Tests\Integration\TestKernel;
use UtafitiLabs\ElementBundle\UtafitiLabsElementBundle;

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

    public function testTheFrameIsTheCardAndTheTabNamesAndCountsTheTable(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'note' => 'how deep it is, and what is moored there',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'North 12']], ['cells' => ['name' => 'South 4']]],
        ]);

        self::assertStringContainsString('<div class="c el-reg"', $html);
        self::assertStringContainsString('<span class="tab">Berths<span class="src">· 2 · how deep it is, and what is moored there</span></span>', $html);
        self::assertStringContainsString('<table class="tbl"', $html);
    }

    public function testAColumnIsAHeaderCellAndACellPerRow(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'columns' => [
                ['key' => 'name', 'label' => 'Berth'],
                ['key' => 'depth', 'label' => 'Depth', 'numeric' => true],
            ],
            'rows' => [['cells' => ['name' => 'North 12', 'depth' => '4']]],
        ]);

        self::assertStringContainsString('<th>Berth</th>', $html);
        self::assertStringContainsString('<th class="num">Depth</th>', $html);
        self::assertStringContainsString('<td>North 12</td>', $html);
        self::assertStringContainsString('<td class="num">4</td>', $html);
    }

    public function testACellIsEscapedBecauseABerthNameIsData(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
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
            'title' => 'Berths',
            'sort' => 'depth',
            'direction' => 'descending',
            'columns' => [
                ['key' => 'name', 'label' => 'Berth', 'sortUrl' => '?sort=name'],
                ['key' => 'depth', 'label' => 'Depth', 'sortUrl' => '?sort=seats'],
            ],
            'rows' => [],
        ]);

        self::assertStringContainsString('<th><a href="?sort=name">Berth</a></th>', $html);
        self::assertStringContainsString('<th class="sorted" aria-sort="descending"><a href="?sort=seats">Depth</a></th>', $html);
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
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth'], ['key' => 'depth', 'label' => 'Depth']],
            'rows' => [
                ['id' => 'north-12', 'name' => 'North 12', 'foldable' => true, 'cells' => ['name' => 'North 12']],
                ['id' => 'south-4', 'name' => 'South 4', 'foldable' => true, 'cells' => ['name' => 'South 4']],
            ],
        ]);

        self::assertStringContainsString('<th class="fchev-h"></th>', $html);
        self::assertStringContainsString('<td class="fchev-c">', $html);
        self::assertStringContainsString('data-fold-id="north-12"', $html);
        self::assertStringContainsString('data-fold-name="North 12"', $html);
        self::assertSame(2, substr_count($html, 'class="foldrow"'));
        self::assertSame(2, substr_count($html, '<div class="foldbox"><div class="fold-in">'));
        // Three columns: the chevron the component owns plus the caller's two.
        self::assertSame(2, substr_count($html, '<td colspan="3">'));
    }

    public function testTheServerRendersARowOpenWhenTheAddressNamesIt(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'open' => 'north-12',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [
                ['id' => 'north-12', 'name' => 'North 12', 'foldable' => true, 'cells' => ['name' => 'North 12']],
                ['id' => 'south-4', 'name' => 'South 4', 'foldable' => true, 'cells' => ['name' => 'South 4']],
            ],
        ]);

        self::assertStringContainsString('<tr class="open" data-fold-id="north-12" data-fold-name="North 12">', $html);
        self::assertStringContainsString('<tr class="foldrow open">', $html);
        self::assertStringContainsString('<tr data-fold-id="south-4" data-fold-name="South 4">', $html);
        self::assertSame(1, substr_count($html, 'aria-expanded="true"'));
        self::assertSame(1, substr_count($html, 'aria-expanded="false"'));
        self::assertStringContainsString('aria-label="Collapse North 12"', $html);
        self::assertStringContainsString('aria-label="Expand South 4"', $html);
    }

    /**
     * THE CHEVRON'S CLICK IS THE CONTROLLER'S, and the identifier it names is
     * the one the manifest publishes — the JS↔template seam of this bundle.
     */
    public function testTheTableWiresTheFoldControllerByItsPublishedIdentifier(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['id' => 'north-12', 'name' => 'North 12', 'foldable' => true, 'cells' => ['name' => 'North 12']]],
        ]);

        $controller = UtafitiLabsElementBundle::FOLD_CONTROLLER;

        self::assertStringContainsString('data-controller="'.$controller.'"', $html);
        self::assertStringContainsString('data-action="'.$controller.'#toggle"', $html);
        self::assertStringContainsString('data-'.$controller.'-param-value="open"', $html);
    }

    public function testATableThatFoldsNothingGrowsNoChevronColumn(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'North 12']]],
        ]);

        self::assertStringNotContainsString('fchev', $html);
        self::assertStringNotContainsString('foldrow', $html);
        self::assertStringNotContainsString('data-controller', $html);
    }

    public function testAnEmptyRegisterSaysSoInsteadOfDrawingNothing(): void
    {
        $html = $this->render([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
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
            <twig:Element:Table title="Berths" :columns="columns" :rows="rows">
                <twig:block name="filters"><div class="lfilt">a filter bar</div></twig:block>
                <twig:block name="row"><td><b>{{ row.cell('name') }}</b></td></twig:block>
                <twig:block name="fold"><p>moorings under {{ row.name }}</p></twig:block>
            </twig:Element:Table>
            TWIG, [
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['id' => 'north-12', 'name' => 'North 12', 'foldable' => true, 'cells' => ['name' => 'North 12']]],
        ]);

        self::assertStringContainsString('<div class="lfilt">a filter bar</div>', $html);
        self::assertStringContainsString('<td><b>North 12</b></td>', $html);
        self::assertStringContainsString('<div class="fold-in"><p>moorings under North 12</p></div>', $html);
    }

    public function testAConsumersOwnAttributesLandOnTheCard(): void
    {
        $html = $this->renderSource(
            '<twig:Element:Table title="Berths" :columns="columns" :rows="[]" id="berths" class="preg" />',
            ['columns' => [['key' => 'name', 'label' => 'Berth']]],
        );

        self::assertStringContainsString('<div class="c el-reg preg" id="berths">', $html);
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
