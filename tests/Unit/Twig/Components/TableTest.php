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

namespace UtafitiLabs\ElementBundle\Tests\Unit\Twig\Components;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\Model\TableColumn;
use UtafitiLabs\ElementBundle\Model\TableRow;
use UtafitiLabs\ElementBundle\Twig\Components\Table;

#[CoversClass(Table::class)]
final class TableTest extends TestCase
{
    public function testColumnsAndRowsArriveAsTheArraysATemplateCanWrite(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth'], ['key' => 'depth', 'label' => 'Depth', 'numeric' => true]],
            'rows' => [['cells' => ['name' => 'North 12', 'depth' => '4']]],
        ]);

        self::assertInstanceOf(TableColumn::class, $table->columns[0]);
        self::assertInstanceOf(TableRow::class, $table->rows[0]);
        self::assertSame('North 12', $table->rows[0]->cell('name'));
    }

    public function testValueObjectsPassStraightThrough(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'columns' => [new TableColumn('name', 'Berth')],
            'rows' => [new TableRow(['name' => 'North 12'])],
        ]);

        self::assertSame('Berth', $table->columns[0]->label);
        self::assertSame('North 12', $table->rows[0]->cell('name'));
    }

    /**
     * THE TAB NAMES THE TABLE AND COUNTS IT — "Berths · 7". The count is not
     * a caption a caller retypes: left unsaid it is how many rows there are.
     */
    public function testTheTabCountsTheRowsUnlessTheCallerKnowsBetter(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'A']], ['cells' => ['name' => 'B']]],
        ]);

        self::assertSame(2, $table->count);
        self::assertSame('· 2', $table->src());

        $paged = $this->mounted([
            'title' => 'Berths',
            'count' => 41,
            'note' => 'how deep it is, and what is moored there',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'A']]],
        ]);

        self::assertSame('· 41 · how deep it is, and what is moored there', $paged->src());
    }

    public function testACountThatIsDeliberatelyAbsentLeavesTheTabAName(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'count' => false,
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'A']]],
        ]);

        self::assertNull($table->count);
        self::assertSame('', $table->src());
    }

    public function testATableFoldsOnlyWhenARowDoes(): void
    {
        self::assertFalse($this->mounted([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['cells' => ['name' => 'A']]],
        ])->foldable());

        self::assertTrue($this->mounted([
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
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
            'title' => 'Berths',
            'columns' => [['key' => 'name', 'label' => 'Berth'], ['key' => 'depth', 'label' => 'Depth']],
            'rows' => [['id' => 'a', 'foldable' => true, 'cells' => ['name' => 'A']]],
        ]);

        self::assertSame(3, $table->colspan());
    }

    public function testTheAddressDecidesWhichRowsAreOpen(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'open' => ['north-12', 'east-7'],
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [
                ['id' => 'north-12', 'foldable' => true, 'cells' => ['name' => 'North 12']],
                ['id' => 'south-4', 'foldable' => true, 'cells' => ['name' => 'South 4']],
            ],
        ]);

        self::assertTrue($table->isOpen($table->rows[0]));
        self::assertFalse($table->isOpen($table->rows[1]));
    }

    public function testTheOpenSetIsAlsoAcceptedTheWayTheAddressSpellsIt(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'open' => 'north-12,east-7',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
            'rows' => [['id' => 'east-7', 'foldable' => true, 'cells' => ['name' => 'East 7']]],
        ]);

        self::assertSame(['north-12', 'east-7'], $table->open);
        self::assertTrue($table->isOpen($table->rows[0]));
    }

    public function testOnlyTheSortedColumnSaysSoAndSaysWhichWay(): void
    {
        $table = $this->mounted([
            'title' => 'Berths',
            'sort' => 'depth',
            'direction' => 'descending',
            'columns' => [['key' => 'name', 'label' => 'Berth'], ['key' => 'depth', 'label' => 'Depth']],
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
            'title' => 'Berths',
            'direction' => 'downwards',
            'columns' => [['key' => 'name', 'label' => 'Berth']],
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
