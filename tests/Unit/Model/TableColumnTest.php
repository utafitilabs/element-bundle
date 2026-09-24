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

namespace Uhifadhi\Element\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Uhifadhi\Element\Model\TableColumn;

#[CoversClass(TableColumn::class)]
final class TableColumnTest extends TestCase
{
    public function testAKeyAndALabelAreEnough(): void
    {
        $column = new TableColumn('seats', 'Seats');

        self::assertSame('seats', $column->key);
        self::assertSame('Seats', $column->label);
        self::assertNull($column->sortUrl);
        self::assertFalse($column->numeric);
    }

    public function testAColumnIsBuiltFromTheArrayATemplateCanWrite(): void
    {
        $column = TableColumn::fromArray([
            'key' => 'seats',
            'label' => 'Seats',
            'sortUrl' => '/team/positions?sort=seats',
            'numeric' => true,
        ]);

        self::assertSame('/team/positions?sort=seats', $column->sortUrl);
        self::assertTrue($column->numeric);
    }

    public function testAColumnWithoutAKeyIsRefusedRatherThanRenderedEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('key');

        TableColumn::fromArray(['label' => 'Seats']);
    }

    /**
     * A NUMERIC COLUMN IS RIGHT-ALIGNED AND MONO — the house's `.num`, so a
     * column of figures can be compared down the page. The class is the
     * component's to emit; a consumer names the FACT, not the class.
     */
    public function testANumericColumnCarriesTheHouseNumberClass(): void
    {
        self::assertSame('num', new TableColumn('seats', 'Seats', numeric: true)->cellClass());
        self::assertSame('', new TableColumn('name', 'Name')->cellClass());
    }
}
