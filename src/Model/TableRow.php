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

namespace UtafitiLabs\ElementBundle\Model;

/**
 * ONE SUBJECT PER ROW.
 *
 * `cells` answers the columns; `id` is the row's identity in the ADDRESS, which
 * is where the open set lives, and `name` is what a reader who cannot see the
 * chevron is told it will expand. A row folds only when it is told to: a
 * chevron that opens onto nothing is worse than no chevron.
 */
final class TableRow
{
    /** @var array<string, string> */
    public readonly array $cells;

    /**
     * @param array<string, string|int|float|\Stringable|null> $cells
     */
    public function __construct(
        array $cells = [],
        public ?string $id = null,
        public ?string $name = null,
        public bool $foldable = false,
    ) {
        if ($foldable && (null === $id || '' === $id)) {
            throw new \InvalidArgumentException('A row that folds needs an id: the open set rides in the address, so a row without one shuts on every reload.');
        }

        $strings = [];
        foreach ($cells as $key => $value) {
            $strings[$key] = null === $value ? '' : (string) $value;
        }

        $this->cells = $strings;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        /** @var array<string, string|int|float|\Stringable|null> $cells */
        $cells = \is_array($row['cells'] ?? null) ? $row['cells'] : [];

        return new self(
            $cells,
            \is_string($row['id'] ?? null) ? $row['id'] : null,
            \is_string($row['name'] ?? null) ? $row['name'] : null,
            (bool) ($row['foldable'] ?? false),
        );
    }

    /**
     * A column this row says nothing about renders EMPTY. A register is not a
     * form: a fact nobody recorded is a blank cell, not an error and not a 0.
     */
    public function cell(string $key): string
    {
        return $this->cells[$key] ?? '';
    }
}
