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

namespace UtafitiLabs\ElementBundle\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use UtafitiLabs\ElementBundle\Model\TableColumn;

#[CoversClass(TableColumn::class)]
final class TableColumnTest extends TestCase
{
    public function testAKeyAndALabelAreEnough(): void
    {
        $column = new TableColumn('depth', 'Depth');

        self::assertSame('depth', $column->key);
        self::assertSame('Depth', $column->label);
        self::assertNull($column->sortUrl);
        self::assertFalse($column->numeric);
    }

    public function testAColumnIsBuiltFromTheArrayATemplateCanWrite(): void
    {
        $column = TableColumn::fromArray([
            'key' => 'depth',
            'label' => 'Depth',
            'sortUrl' => '/team/berths?sort=seats',
            'numeric' => true,
        ]);

        self::assertSame('/team/berths?sort=seats', $column->sortUrl);
        self::assertTrue($column->numeric);
    }

    public function testAColumnWithoutAKeyIsRefusedRatherThanRenderedEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('key');

        TableColumn::fromArray(['label' => 'Depth']);
    }

    /**
     * A NUMERIC COLUMN IS RIGHT-ALIGNED AND MONO — the library's `.num`, so a
     * column of figures can be compared down the page. The class is the
     * component's to emit; a consumer names the FACT, not the class.
     */
    public function testANumericColumnCarriesTheNumberClass(): void
    {
        self::assertSame('num', new TableColumn('depth', 'Depth', numeric: true)->cellClass());
        self::assertSame('', new TableColumn('name', 'Name')->cellClass());
    }
}
