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
use Uhifadhi\Element\Model\TableRow;

#[CoversClass(TableRow::class)]
final class TableRowTest extends TestCase
{
    public function testARowIsItsCells(): void
    {
        $row = new TableRow(['name' => 'Warden', 'seats' => '4']);

        self::assertSame('Warden', $row->cell('name'));
        self::assertSame('', $row->cell('holders'), 'A column with no cell renders empty, never an error.');
        self::assertNull($row->id);
        self::assertFalse($row->foldable, 'A row folds only when it is told to.');
    }

    public function testARowThatFoldsCarriesTheIdentityTheAddressWillName(): void
    {
        $row = new TableRow(['name' => 'Warden'], id: 'warden', name: 'Warden', foldable: true);

        self::assertTrue($row->foldable);
        self::assertSame('warden', $row->id);
        self::assertSame('Warden', $row->name);
    }

    public function testARowIsBuiltFromTheArrayATemplateCanWrite(): void
    {
        $row = TableRow::fromArray([
            'id' => 'warden',
            'name' => 'Warden',
            'foldable' => true,
            'cells' => ['name' => 'Warden'],
        ]);

        self::assertSame('warden', $row->id);
        self::assertTrue($row->foldable);
        self::assertSame('Warden', $row->cell('name'));
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

        new TableRow(['name' => 'Warden'], foldable: true);
    }
}
