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

namespace Uhifadhi\Element\Model;

/**
 * ONE FACT PER COLUMN — the register idiom, as a value.
 *
 * A column names the fact (`key`), says how it is headed (`label`), whether the
 * register sorts by it (`sortUrl` — a sortable header IS a link, and the server
 * owns where it goes), and whether it holds figures (`numeric`). It carries no
 * class: a caller names the FACT and the component decides the vocabulary, so a
 * seat count is right-aligned and mono in every register in the product without
 * anyone remembering to say `.num`.
 */
final class TableColumn
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $sortUrl = null,
        public bool $numeric = false,
    ) {
        if ('' === $key) {
            throw new \InvalidArgumentException('A table column needs a key: it is the name of the fact each row answers.');
        }
    }

    /**
     * @param array<string, mixed> $column
     */
    public static function fromArray(array $column): self
    {
        if (!isset($column['key']) || !\is_string($column['key'])) {
            throw new \InvalidArgumentException('A table column needs a "key".');
        }

        return new self(
            $column['key'],
            \is_string($column['label'] ?? null) ? $column['label'] : '',
            \is_string($column['sortUrl'] ?? null) ? $column['sortUrl'] : null,
            (bool) ($column['numeric'] ?? false),
        );
    }

    /**
     * The house class for this column's cells, or an empty string where the
     * cell is ordinary — never a null the template has to think about.
     */
    public function cellClass(): string
    {
        return $this->numeric ? 'num' : '';
    }
}
