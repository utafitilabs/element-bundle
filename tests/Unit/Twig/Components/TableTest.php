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

namespace Uhifadhi\Element\Tests\Unit\Twig\Components;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Uhifadhi\Element\Model\TableColumn;
use Uhifadhi\Element\Model\TableRow;
use Uhifadhi\Element\Twig\Components\Table;

#[CoversClass(Table::class)]
final class TableTest extends TestCase
{
    public function testColumnsAndRowsArriveAsTheArraysATemplateCanWrite(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position'], ['key' => 'seats', 'label' => 'Seats', 'numeric' => true]],
            'rows' => [['cells' => ['name' => 'Warden', 'seats' => '4']]],
        ]);

        self::assertInstanceOf(TableColumn::class, $table->columns[0]);
        self::assertInstanceOf(TableRow::class, $table->rows[0]);
        self::assertSame('Warden', $table->rows[0]->cell('name'));
    }

    public function testValueObjectsPassStraightThrough(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'columns' => [new TableColumn('name', 'Position')],
            'rows' => [new TableRow(['name' => 'Warden'])],
        ]);

        self::assertSame('Position', $table->columns[0]->label);
        self::assertSame('Warden', $table->rows[0]->cell('name'));
    }

    /**
     * THE TAB NAMES THE TABLE AND COUNTS IT — "Positions · 7". The count is not
     * a caption a caller retypes: left unsaid it is how many rows there are.
     */
    public function testTheTabCountsTheRowsUnlessTheCallerKnowsBetter(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'A']], ['cells' => ['name' => 'B']]],
        ]);

        self::assertSame(2, $table->count);
        self::assertSame('· 2', $table->src());

        $paged = $this->mounted([
            'title' => 'Positions',
            'count' => 41,
            'note' => 'what each grants, and who holds it',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'A']]],
        ]);

        self::assertSame('· 41 · what each grants, and who holds it', $paged->src());
    }

    public function testACountThatIsDeliberatelyAbsentLeavesTheTabAName(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'count' => false,
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'A']]],
        ]);

        self::assertNull($table->count);
        self::assertSame('', $table->src());
    }

    public function testATableFoldsOnlyWhenARowDoes(): void
    {
        self::assertFalse($this->mounted([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['cells' => ['name' => 'A']]],
        ])->foldable());

        self::assertTrue($this->mounted([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['id' => 'a', 'foldable' => true, 'cells' => ['name' => 'A']]],
        ])->foldable());
    }

    /**
     * THE CHEVRON COLUMN IS THE COMPONENT'S, NOT THE CALLER'S — so the fold row
     * underneath has to span the caller's columns PLUS it.
     */
    public function testTheFoldRowSpansEveryColumnIncludingTheChevron(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'columns' => [['key' => 'name', 'label' => 'Position'], ['key' => 'seats', 'label' => 'Seats']],
            'rows' => [['id' => 'a', 'foldable' => true, 'cells' => ['name' => 'A']]],
        ]);

        self::assertSame(3, $table->colspan());
    }

    public function testTheAddressDecidesWhichRowsAreOpen(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'open' => ['warden', 'ranger'],
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [
                ['id' => 'warden', 'foldable' => true, 'cells' => ['name' => 'Warden']],
                ['id' => 'scout', 'foldable' => true, 'cells' => ['name' => 'Scout']],
            ],
        ]);

        self::assertTrue($table->isOpen($table->rows[0]));
        self::assertFalse($table->isOpen($table->rows[1]));
    }

    public function testTheOpenSetIsAlsoAcceptedTheWayTheAddressSpellsIt(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'open' => 'warden,ranger',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [['id' => 'ranger', 'foldable' => true, 'cells' => ['name' => 'Ranger']]],
        ]);

        self::assertSame(['warden', 'ranger'], $table->open);
        self::assertTrue($table->isOpen($table->rows[0]));
    }

    public function testOnlyTheSortedColumnSaysSoAndSaysWhichWay(): void
    {
        $table = $this->mounted([
            'title' => 'Positions',
            'sort' => 'seats',
            'direction' => 'descending',
            'columns' => [['key' => 'name', 'label' => 'Position'], ['key' => 'seats', 'label' => 'Seats']],
            'rows' => [],
        ]);

        self::assertSame('', $table->headerClass($table->columns[0]));
        self::assertSame('sorted', $table->headerClass($table->columns[1]));
        self::assertTrue($table->isSorted($table->columns[1]));
        self::assertSame('descending', $table->direction);
    }

    public function testAnUnknownSortDirectionIsRefusedRatherThanPrintedIntoAriaSort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('direction');

        $this->mounted([
            'title' => 'Positions',
            'direction' => 'downwards',
            'columns' => [['key' => 'name', 'label' => 'Position']],
            'rows' => [],
        ]);
    }

    /**
     * Mounts the component the way TwigComponentBundle does: #[PreMount] first,
     * then the props, then #[PostMount].
     *
     * @param array<string, mixed> $props
     */
    private function mounted(array $props): Table
    {
        $table = new Table();
        $props = $table->preMount($props);

        foreach ($props as $name => $value) {
            $table->{$name} = $value;
        }

        $table->postMount();

        return $table;
    }
}
