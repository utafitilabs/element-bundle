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

namespace Uhifadhi\Element\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Uhifadhi\Element\DependencyInjection\ElementConfiguration;
use Uhifadhi\Element\Model\TableColumn;
use Uhifadhi\Element\Model\TableRow;
use Uhifadhi\Element\UhifadhiElementBundle;

/**
 * `<twig:Element:Table>` — THE HOUSE REGISTER.
 *
 * One table in a house card, its tab naming and counting it, its headers
 * sortable, and its rows able to fold open onto a panel. The fold is the ruled
 * contract of 2026-09-22 and is ported verbatim: a folding row carries a
 * chevron cell and its identity, ONE `tr.foldrow` follows it holding
 * `.foldbox > .fold-in > content`, the server renders a row open where the
 * address names it, and the sheet — never this class — owns the motion.
 *
 * NO AsTwigComponent ATTRIBUTE, DELIBERATELY. A reusable bundle does not
 * autoconfigure, so the attribute would never be read; the `twig.component` tag
 * is applied by hand in config/services.php with the same key and template the
 * attribute would have carried.
 *
 * @see https://symfony.com/bundles/ux-twig-component/current/index.html
 * @see UhifadhiElementBundle::FOLD_CONTROLLER for the JS half of the fold
 */
final class Table
{
    /** The card tab's name — what this register is, in one or two words. */
    public string $title = '';

    /** The tab's qualifier, sentence case: "what each grants, and who holds it". */
    public ?string $note = null;

    /**
     * How many rows there are in the register — not how many are on this page.
     * Left unsaid it is the number of rows given; passed `false` the tab is a
     * name and nothing else.
     */
    public ?int $count = null;

    /** Whether the tab counts at all. Set from `count: false`. */
    public bool $counted = true;

    /** @var list<TableColumn> */
    public array $columns = [];

    /** @var list<TableRow> */
    public array $rows = [];

    /** The key of the column the register is ordered by, if any. */
    public ?string $sort = null;

    /** How that column is ordered, in `aria-sort`'s own vocabulary. */
    public string $direction = self::ASCENDING;

    /**
     * The rows the address asked for, open.
     *
     * @var list<string>
     */
    public array $open = [];

    /** The address parameter the open set is written back to. */
    public string $foldParam = 'open';

    /** The Stimulus identifier that owns the chevron's click. */
    public string $controller = UhifadhiElementBundle::FOLD_CONTROLLER;

    /** What an empty register says. */
    public string $emptyText;

    /**
     * THE VIEW, PREPARED. TwigComponent's `this` proxy refuses a computed
     * method that takes arguments
     * (vendor/symfony/ux-twig-component/src/ComputedPropertiesProxy.php), and
     * every per-row and per-column question this component answers takes one.
     * So the answers are worked out once, here, and the template iterates them
     * — which also keeps the template a view and the decisions unit-tested.
     *
     * @var list<array{label: string, class: string, sortUrl: ?string, sorted: bool}>
     */
    public array $headers = [];

    /** @var list<array{row: TableRow, open: bool, label: string}> */
    public array $body = [];

    public const string ASCENDING = 'ascending';
    public const string DESCENDING = 'descending';

    public function __construct(string $emptyText = ElementConfiguration::DEFAULT_EMPTY_TEXT)
    {
        $this->emptyText = $emptyText;
    }

    /**
     * COLUMNS AND ROWS ARRIVE AS THE ARRAYS A TEMPLATE CAN WRITE. A caller with
     * value objects passes them straight through; a caller holding a query
     * result writes the literal a Twig template can express. Both end up as the
     * same objects before a single cell is drawn.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    #[PreMount]
    public function preMount(array $props): array
    {
        if (isset($props['columns']) && \is_array($props['columns'])) {
            $props['columns'] = array_values(array_map(
                static fn (mixed $column): TableColumn => $column instanceof TableColumn ? $column : TableColumn::fromArray(self::toArray($column, 'column')),
                $props['columns'],
            ));
        }

        if (isset($props['rows']) && \is_array($props['rows'])) {
            $props['rows'] = array_values(array_map(
                static fn (mixed $row): TableRow => $row instanceof TableRow ? $row : TableRow::fromArray(self::toArray($row, 'row')),
                $props['rows'],
            ));
        }

        // The address hands the open set over as it spells it — "a,b" — and a
        // caller that already split it hands over the list. Neither is asked to
        // know about the other.
        if (isset($props['open']) && \is_string($props['open'])) {
            $props['open'] = array_values(array_filter(array_map(trim(...), explode(',', $props['open'])), static fn (string $id): bool => '' !== $id));
        }

        if (false === ($props['count'] ?? null)) {
            unset($props['count']);
            $props['counted'] = false;
        }

        return $props;
    }

    #[PostMount]
    public function postMount(): void
    {
        if (!\in_array($this->direction, [self::ASCENDING, self::DESCENDING], true)) {
            throw new \InvalidArgumentException(\sprintf('A register is sorted "%s" or "%s"; "%s" is not a direction aria-sort can announce.', self::ASCENDING, self::DESCENDING, $this->direction));
        }

        if ($this->counted && null === $this->count) {
            $this->count = \count($this->rows);
        }

        if (!$this->counted) {
            $this->count = null;
        }

        $this->headers = array_map(fn (TableColumn $column): array => [
            'label' => $column->label,
            'class' => $this->headerClass($column),
            'sortUrl' => $column->sortUrl,
            'sorted' => $this->isSorted($column),
        ], $this->columns);

        $this->body = array_map(fn (TableRow $row): array => [
            'row' => $row,
            'open' => $this->isOpen($row),
            'label' => $this->chevronLabel($row),
        ], $this->rows);
    }

    /**
     * The tab's qualifier — the count and the note, in the house's own
     * punctuation, or nothing at all.
     */
    public function src(): string
    {
        $parts = [];

        if (null !== $this->count) {
            $parts[] = (string) $this->count;
        }

        if (null !== $this->note && '' !== $this->note) {
            $parts[] = $this->note;
        }

        return [] === $parts ? '' : '· '.implode(' · ', $parts);
    }

    /**
     * THE CHEVRON COLUMN EXISTS ONLY WHERE SOMETHING FOLDS. A register of flat
     * rows draws no empty first column and wires no controller.
     */
    public function foldable(): bool
    {
        foreach ($this->rows as $row) {
            if ($row->foldable) {
                return true;
            }
        }

        return false;
    }

    /** The caller's columns, plus the chevron column when there is one. */
    public function colspan(): int
    {
        return \count($this->columns) + ($this->foldable() ? 1 : 0);
    }

    public function isOpen(TableRow $row): bool
    {
        return null !== $row->id && \in_array($row->id, $this->open, true);
    }

    public function isSorted(TableColumn $column): bool
    {
        return null !== $this->sort && $column->key === $this->sort;
    }

    /**
     * The header cell's class: the sorted column says so, and every other
     * column says nothing.
     */
    public function headerClass(TableColumn $column): string
    {
        $classes = array_filter([$column->cellClass(), $this->isSorted($column) ? 'sorted' : '']);

        return implode(' ', $classes);
    }

    /**
     * What a reader who cannot see the chevron is told it will do. The row's
     * name is the subject; without one the action still has to be announced.
     */
    public function chevronLabel(TableRow $row): string
    {
        return trim(($this->isOpen($row) ? 'Collapse ' : 'Expand ').($row->name ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private static function toArray(mixed $value, string $what): array
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(\sprintf('A table %s is a %s object or the array one is built from, got "%s".', $what, $what, get_debug_type($value)));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }
}
