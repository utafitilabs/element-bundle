# Element for Symfony!

[![CI](https://github.com/utafitilabs/element-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/utafitilabs/element-bundle/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/utafitilabs/element-bundle)](https://packagist.org/packages/utafitilabs/element-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/utafitilabs/element-bundle)](https://packagist.org/packages/utafitilabs/element-bundle)
[![License](https://img.shields.io/packagist/l/utafitilabs/element-bundle)](LICENSE)

A **Twig component library** for Symfony: components styled with Tailwind that an
application renders its vocabulary with — so a register drawn on one page and a
register drawn on another are the same object, not two ports of one drawing. One
built stylesheet, no build step for the consumer, and **the whole look comes from
configuration**: two products install this bundle unchanged and differ by colour
and by type.

> Part of the [utafiti tools](https://github.com/utafitilabs).

## Contents

- [What it is](#what-it-is)
- [Install](#install)
- [A taste](#a-taste)
- [The theme](#the-theme)
  - [The palette](#the-palette)
  - [The categorical hues](#the-categorical-hues)
  - [Type, radius and control height](#type-radius-and-control-height)
  - [Calling `element_theme()`](#calling-element_theme)
  - [Tailwind in the consuming application](#tailwind-in-the-consuming-application)
- [The components](#the-components)
- [The fold contract](#the-fold-contract)
- [Adding a component](#adding-a-component)
- [Building the stylesheet](#building-the-stylesheet)
- [The style guide](#the-style-guide)
- [Requirements](#requirements)
- [Learn more](#learn-more)
- [License](#license)

## What it is

- **Components, not snippets.** A screen says `<twig:Element:Table …>` and gets
  the register — the card, the tab that names and counts it, sortable headers,
  and rows that fold open. What the register is *about* is the caller's; what it
  looks like is nobody's, because it is the same everywhere.
- **Themed from configuration.** The shipped stylesheet paints with custom
  properties and decides not one value. Colours, the categorical hues, both type
  stacks, the corner radius and the control height are stated in
  `config/packages/element.yaml` and written into the page by `element_theme()`.
- **Tailwind, built here.** The stylesheet is a build output, committed. A
  reusable bundle cannot run an application's asset pipeline, so what it ships is
  a finished file — and the theme it is built from is exported whole, so an
  application's own Tailwind build can reuse the same colours.
- **Zero-config.** Registering the bundle publishes the component namespace and
  the assets directory. An application writes nothing to render a component.

## Install

Applications using [Symfony Flex](https://symfony.com/doc/current/setup.html):

```console
composer require utafitilabs/element-bundle
```

Applications without Symfony Flex — after requiring the package, enable the
bundle:

```php
// config/bundles.php
return [
    // ...
    UtafitiLabs\ElementBundle\UtafitiLabsElementBundle::class => ['all' => true],
];
```

That one line registers the `Element` component namespace with
TwigComponentBundle (so `<twig:Element:Table>` resolves), maps this bundle's
`assets/` directory into AssetMapper under `@utafitilabs/element-bundle`, and
serves `public/` as `bundles/utafitilabselement/…`.

**Two lines the application still owns**, because no bundle can write them:

```json
// assets/controllers.json — enables the fold controller in this application
{
    "controllers": {
        "@utafitilabs/element-bundle": {
            "register-fold": { "enabled": true, "fetch": "eager" }
        }
    }
}
```

```twig
{# every page that draws a component: the theme, then the sheet that spends it #}
{{ element_theme() }}
<link rel="stylesheet" href="{{ asset(constant('UtafitiLabs\\ElementBundle\\UtafitiLabsElementBundle::STYLESHEET')) }}">
```

Flex writes the first of those itself, from this package's own
`assets/package.json`.

## A taste

```twig
<twig:Element:Table
    title="Berths"
    note="how deep it is, and what is moored there"
    :count="total"
    :columns="[
        { key: 'berth', label: 'Berth', sortUrl: path('berths', { sort: 'berth' }) },
        { key: 'depth', label: 'Depth', numeric: true }
    ]"
    :rows="rows"
    sort="berth"
    :open="app.request.query.get('open')"
>
    <twig:block name="filters">{{ include('_filters.html.twig') }}</twig:block>

    <twig:block name="row">
        <td><a href="{{ path('berth', { id: row.id }) }}">{{ row.cell('berth') }}</a></td>
        <td class="num">{{ row.cell('depth') }}</td>
    </twig:block>

    <twig:block name="fold">
        {{ include('_moorings.html.twig', { berth: row.id }) }}
    </twig:block>
</twig:Element:Table>
```

## The theme

Everything the stylesheet paints with lives under `element.theme`, and every node
has a default — so a theme states only what it changes. Light and dark are two
complete palettes, not a filter over one; the dark one hangs off
`<html class="dark">`, which the application puts there before the first paint.

This is the whole of the shipped default:

```yaml
# config/packages/element.yaml
element:
    theme:
        colors:
            ground: 'rgb(243 242 235)'        # the page behind everything
            surface: 'rgb(255 255 255)'       # a card, at the top of its gradient
            surface_low: 'rgb(251 250 246)'   # the same card, at the bottom of it
            raised: 'rgb(236 235 226)'        # chips, fields, anything on a card
            ink: 'rgb(27 38 32)'              # primary text
            muted: 'rgb(87 100 90)'           # secondary text, and the rules mixed from it
            faint: 'rgb(118 132 122)'         # tertiary text
            accent: 'rgb(15 138 104)'         # the one accent: live state and calls to action
            accent_ink: 'rgb(255 255 255)'    # text that survives on the accent
            ok: 'rgb(30 122 70)'
            warn: 'rgb(154 107 20)'
            danger: 'rgb(186 66 39)'
            danger_ink: 'rgb(255 255 255)'
            critical: 'rgb(143 29 14)'
            line: 'rgba(23, 38, 30, 0.11)'
            line_strong: 'rgba(23, 38, 30, 0.20)'
        hues:                                 # for telling categories apart
            moss: '#1E7A46'
            ochre: '#8A6410'
            sky: '#15678F'
            rose: '#A83A52'
            plum: '#6A3FA8'
            indigo: '#3F3FB5'
            rust: '#94500D'
            magenta: '#8E2C70'
            slate: '#4C5C66'
            olive: '#5D622F'
            mauve: '#70518D'
            clay: '#894F3F'
            brick: '#AB341F'
            teal: '#096B5A'
            fern: '#376A0C'
            cobalt: '#265BB4'
            mulberry: '#894B64'
            orchid: '#903A9B'
        shadow: '0 6px 22px rgba(23, 38, 30, 0.10)'
        fonts:
            ui: '-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif'
            mono: 'ui-monospace, SFMono-Regular, Menlo, monospace'
        radius: '14px'
        control_height: '32px'
        dark:
            colors:
                ground: 'rgb(12 19 16)'
                surface: 'rgb(21 32 25)'
                surface_low: 'rgb(17 26 20)'
                raised: 'rgb(28 41 33)'
                ink: 'rgb(234 242 236)'
                muted: 'rgb(150 168 156)'
                faint: 'rgb(124 140 129)'
                accent: 'rgb(62 217 168)'
                accent_ink: 'rgb(12 19 16)'
                ok: 'rgb(99 201 127)'
                warn: 'rgb(219 163 63)'
                danger: 'rgb(224 91 65)'
                danger_ink: 'rgb(255 255 255)'
                critical: 'rgb(255 74 56)'
                line: 'rgba(216, 236, 224, 0.09)'
                line_strong: 'rgba(216, 236, 224, 0.17)'
            hues:
                moss: '#4FA86A'
                ochre: '#EFD27E'
                sky: '#5FB0E8'
                rose: '#E8899B'
                plum: '#B68BE8'
                indigo: '#8A87E8'
                rust: '#E0742E'
                magenta: '#DA7CB6'
                slate: '#9DAAB4'
                olive: '#A7A981'
                mauve: '#B69CCB'
                clay: '#CB9B8C'
                brick: '#ED8C74'
                teal: '#78B1A2'
                fern: '#8DB06D'
                cobalt: '#91A3E9'
                mulberry: '#CA98AA'
                orchid: '#D08FD6'
            shadow: '0 8px 26px rgba(0, 0, 0, 0.34)'
    table:
        empty_text: 'Nothing here yet.'       # what a register says when it has no rows
```

A value is written into a `<style>` element, so `<`, `>`, `{`, `}` and `;` are
refused when the container is built rather than escaped when a page is drawn.

### The palette

Each channel is rendered as `--e-<channel>`: `--e-ground`, `--e-ink`,
`--e-accent`, `--e-line-strong`, and so on (an underscore in the key becomes a
hyphen in the property). A product states the handful it wants said differently:

```yaml
element:
    theme:
        colors: { accent: '#0f62fe', accent_ink: 'white' }
        dark:
            colors: { accent: '#78a9ff' }
```

### The categorical hues

Eighteen colours for telling **categories** apart — named by what they look like,
because what they *mean* is the product's. A product maps its own categories onto
them: **pick by name where a meaning has to stay put, by index where a series
just needs colours that differ.**

```twig
<span style="color: var(--e-hue-moss)">…</span>      {# a meaning that must not move #}
<span style="color: var(--e-hue-{{ loop.index }})">…</span>  {# a series #}
```

`--e-hue-1 … --e-hue-18` are aliases of the named values in the order above, so a
restated hue is restated once and both spellings follow.

The set is open: a product may restate any hue and append its own, and neither
gesture drops the rest.

```yaml
element:
    theme:
        hues: { moss: '#0b6b3a', seagrass: '#00584a' }        # one restated, one appended
        dark:
            hues: { seagrass: '#66ccaa' }                     # state an addition for both grounds
```

The shipped set is measured rather than chosen by eye: every hue stands at least
**12.0 apart from every other in CIE76 Lab distance** (the closest shipped pair
measures 13.3) and clears **4.5:1 contrast against the ground it is drawn on**, in
light and in dark alike. `tests/Unit/Theme/HuePaletteTest.php` asserts both, so
the set stays honest as it grows — run the suite after adding one.

### Type, radius and control height

`fonts.ui` and `fonts.mono` become `--e-font-ui` and `--e-font-mono`; `radius`
becomes `--e-radius` (the corner of a card) and `control_height` becomes
`--e-control-height` (what a control in a component row is built to). They are
stated once: dark changes the palette, never the measurements.

### Calling `element_theme()`

`element_theme()` renders one `<style>` element holding `:root { … }` and
`html.dark { … }`. Call it **once, in `<head>`, before the bundle's stylesheet** —
a rule that reads a property the page has not defined yet paints with nothing:

```twig
<head>
    {{ element_theme() }}
    <link rel="stylesheet" href="{{ asset(constant('UtafitiLabs\\ElementBundle\\UtafitiLabsElementBundle::STYLESHEET')) }}">
</head>
```

It is a lazy Twig runtime, so a page that never calls it builds nothing.

### Tailwind in the consuming application

The built sheet exports the palette as Tailwind theme colours (`--color-ground`,
`--color-ink`, `--color-accent`, …) whose values are the same `var(--e-*)`
properties. An application running its own Tailwind build over its own templates
can `@import` this sheet and write `text-muted` or `bg-ground` and get exactly the
colour the components use, following the configured theme with no second
definition and no rebuild.

## The components

### `<twig:Element:Table>`

| Prop | Type | Meaning |
|---|---|---|
| `title` | `string` | the card tab's name — what this register is |
| `note` | `?string` | the tab's qualifier, sentence case |
| `count` | `?int \| false` | how many rows there are; unsaid, it is the number given; `false` leaves the tab a name |
| `columns` | `list<TableColumn \| array>` | `{ key, label, sortUrl?, numeric? }` |
| `rows` | `list<TableRow \| array>` | `{ cells, id?, name?, foldable? }` |
| `sort` | `?string` | the key of the column the register is ordered by |
| `direction` | `'ascending' \| 'descending'` | how, in `aria-sort`'s own vocabulary |
| `open` | `list<string> \| string` | the rows the address asked for; `"a,b"` is accepted as it is spelt |
| `foldParam` | `string` | the address parameter the open set is written back to (`open`) |
| `controller` | `string` | the Stimulus identifier that owns the chevron's click |
| `emptyText` | `string` | what an empty register says (configured; see `table.empty_text`) |

| Block | Sees | Draws |
|---|---|---|
| `filters` | the component's props | the bar above the table, inside the card |
| `row` | `row`, `isOpen`, `loop` | the row's `<td>` cells |
| `fold` | `row`, `isOpen`, `loop` | what opens underneath the row |
| `empty` | the component's props | what an empty register says |

A column names the **fact** (`numeric: true`), never the class — so a figure is
right-aligned and mono in every register without anyone remembering `.num`.

## The fold contract

A row folds only when it says `foldable: true`, and a foldable row must carry an
`id`: the open set rides in the address (`?open=a,b`), so a row without one would
shut on every reload. A register where nothing folds grows no chevron column and
wires no controller.

The markup is fixed: the folding row carries a chevron cell and its identity, and
**one** `tr.foldrow` follows it holding `.foldbox > .fold-in > content`. The track
eases `0fr → 1fr` over 600ms and the chevron turns on the same curve; the panel's
spacing is the content's **margin**, never padding on the track, which would zero
and jump. The server renders open the rows the address names, and the
`register-fold` Stimulus controller owns nothing but the click — it writes the
open set back into the address and keeps no state of its own.

Every declaration of it is asserted against the built stylesheet by
`tests/Unit/Stylesheet/FoldContractTest.php`.

## Adding a component

1. **Write the tests** — the render test that asserts the markup, and, where the
   component ships CSS, the conformance test that asserts the declarations
   against the BUILT stylesheet.
2. **The class**, in `src/Twig/Components/`, with any value objects it thinks in
   under `src/Model/`. Per-row and per-column decisions are prepared in
   `#[PostMount]`, because `this` refuses computed methods that take arguments.
3. **The template**, in `templates/components/`, controlling its whitespace — the
   markup is asserted literally.
4. **Tag it by hand** in `config/services.php`: `twig.component` with `key`,
   `template` and `expose_public_props: true`. A reusable bundle does not
   autoconfigure.
5. **The styles** go in `assets/styles/element.css`, spending `var(--e-*)` and
   defining nothing; run `composer css:build` and commit input and output
   together.
6. **Add it to the style guide**, and run `composer check`.

## Building the stylesheet

```console
composer css:build
```

That runs `bin/console tailwind:build` against `assets/styles/element.css` and
copies the result to `public/element.css`. `bin/console` boots a dev-only build
kernel; it is the maintainer's, not an application's. **Edit the input file, run
the build, commit both** — CI asserts the committed output and will not rebuild
it, so a change that never ran the build fails loudly instead of shipping.

## The style guide

Every component, with sample rows, on one page: the `/style-guide` route mounted
by `tests/Integration/TestKernel.php`, in both palettes (`?theme=dark`). It is
what the functional suite reads and what a maintainer opens in a browser. A
component library nobody renders drifts.

## Requirements

- **PHP 8.4+**
- **Symfony 7.3 or 8.0**
- **symfony/ux-twig-component 3.5+** — the component runtime
- **symfonycasts/tailwind-bundle 1.0+** — the build; the Tailwind CLI binary is
  downloaded by `tailwind:build` and pinned in the build kernel
- **symfony/stimulus-bundle** (suggested) — without it the fold renders, open
  state and all, and never toggles

## Learn more

- [docs/index.md](docs/index.md) — the documentation entry point
- [docs/design-decisions.md](docs/design-decisions.md) — every deliberate choice,
  why, and the trigger that reopens it

## License

MIT: see the [LICENSE](LICENSE) file for more details.
