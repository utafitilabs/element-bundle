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
use UtafitiLabs\ElementBundle\Model\TableRow;

#[CoversClass(TableRow::class)]
final class TableRowTest extends TestCase
{
    public function testARowIsItsCells(): void
    {
        $row = new TableRow(['name' => 'North 12', 'depth' => '4']);

        self::assertSame('North 12', $row->cell('name'));
        self::assertSame('', $row->cell('holders'), 'A column with no cell renders empty, never an error.');
        self::assertNull($row->id);
        self::assertFalse($row->foldable, 'A row folds only when it is told to.');
    }

    public function testARowThatFoldsCarriesTheIdentityTheAddressWillName(): void
    {
        $row = new TableRow(['name' => 'North 12'], id: 'north-12', name: 'North 12', foldable: true);

        self::assertTrue($row->foldable);
        self::assertSame('north-12', $row->id);
        self::assertSame('North 12', $row->name);
    }

    public function testARowIsBuiltFromTheArrayATemplateCanWrite(): void
    {
        $row = TableRow::fromArray([
            'id' => 'north-12',
            'name' => 'North 12',
            'foldable' => true,
            'cells' => ['name' => 'North 12'],
        ]);

        self::assertSame('north-12', $row->id);
        self::assertTrue($row->foldable);
        self::assertSame('North 12', $row->cell('name'));
    }

    /**
     * A ROW THAT FOLDS WITHOUT AN ID CANNOT BE REOPENED. The open set rides in
     * the address (`?open=a,b`), so a foldable row without an identity would
     * shut on every reload and nobody would be told why.
     */
    public function testAFoldableRowWithoutAnIdIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id');

        new TableRow(['name' => 'North 12'], foldable: true);
    }
}
