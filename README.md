# uhifadhi/element-module

The house component library: Twig components, styled with Tailwind, that the
shell and every module render their vocabulary with — so a register drawn in one
module and a register drawn in another are the same object, not two ports of one
drawing. A [uhifadhi](https://github.com/uhifadhilabs) module bundle.

> **It depends on no other uhifadhi package, on purpose.** A library the platform
> is built out of cannot be built out of the platform.

## Contents

- [What it is](#what-it-is)
- [Installation](#installation)
- [A taste](#a-taste)
- [The components](#the-components)
- [Stylesheets, and which one to link](#stylesheets-and-which-one-to-link)
- [Building the stylesheet](#building-the-stylesheet)
- [The style guide](#the-style-guide)
- [Configuration](#configuration)
- [Requirements](#requirements)
- [Learn more](#learn-more)
- [License](#license)

## What it is

- **Components, not snippets.** A screen says `<twig:Element:Table …>` and gets
  the house register — the card, the tab that names and counts it, sortable
  headers, and rows that fold open. What the register is *about* is the caller's;
  what it looks like is nobody's, because it is the same everywhere.
- **The fold contract, ported verbatim.** A folding row carries a chevron cell
  and its identity; ONE `tr.foldrow` follows it holding
  `.foldbox > .fold-in > content`; the track eases `0fr → 1fr` over 600ms and the
  chevron turns on the same curve; the panel's spacing is the content's MARGIN,
  never padding on the track. The open set rides in the address (`?open=a,b`), so
  the page a reader is looking at is the page they can send, and the server
  renders those rows open. Every declaration of it is asserted against the built
  stylesheet by `tests/Unit/Stylesheet/FoldContractTest.php`.
- **Tailwind, built here.** The stylesheet is a build output, committed. A
  reusable bundle cannot run an installation's asset pipeline, so what it ships
  is a finished file — and the theme it is built from is exported whole, so a
  host's own Tailwind build can reuse the same colours.
- **Zero-config.** Registering the bundle publishes the component namespace and
  the assets directory. An installation writes nothing to render a component.

## Installation

```console
composer require uhifadhi/element-module
```

Without Flex, register the bundle by hand:

```php
// config/bundles.php
return [
    // …
    Uhifadhi\Element\UhifadhiElementBundle::class => ['all' => true],
];
```

That one line registers the `Element` component namespace with
TwigComponentBundle (so `<twig:Element:Table>` resolves), maps this bundle's
`assets/` directory into AssetMapper under `@uhifadhi/element-module`, and serves
`public/` as `bundles/uhifadhielement/…`. Nothing else to configure.

**Two lines an installation still owns**, because no bundle can write them:

```json
// assets/controllers.json — enables the fold controller in this application
{
    "controllers": {
        "@uhifadhi/element-module": {
            "register-fold": { "enabled": true, "fetch": "eager" }
        }
    }
}
```

```twig
{# the page that draws a component links the stylesheet #}
<link rel="stylesheet" href="{{ asset(constant('Uhifadhi\\Element\\UhifadhiElementBundle::STYLESHEET')) }}">
```

## A taste

```twig
<twig:Element:Table
    title="Positions"
    note="what each grants, and who holds it"
    :count="total"
    :columns="[
        { key: 'position', label: 'Position', sortUrl: path('team_positions', { sort: 'position' }) },
        { key: 'seats', label: 'Seats', numeric: true }
    ]"
    :rows="rows"
    sort="position"
    :open="app.request.query.get('open')"
>
    <twig:block name="filters">{{ include('_filters.html.twig') }}</twig:block>

    <twig:block name="row">
        <td><a href="{{ path('team_position', { id: row.id }) }}">{{ row.cell('position') }}</a></td>
        <td class="num">{{ row.cell('seats') }}</td>
    </twig:block>

    <twig:block name="fold">
        {{ include('_holders.html.twig', { position: row.id }) }}
    </twig:block>
</twig:Element:Table>
```

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
| `emptyText` | `string` | what an empty register says (configured; see below) |

| Block | Sees | Draws |
|---|---|---|
| `filters` | the component's props | the bar above the table, inside the card |
| `row` | `row`, `isOpen`, `loop` | the row's `<td>` cells |
| `fold` | `row`, `isOpen`, `loop` | what opens underneath the row |
| `empty` | the component's props | what an empty register says |

A row folds only when it says `foldable: true`, and a foldable row must carry an
`id` — the open set rides in the address, so a row without one would shut on
every reload. A register where nothing folds grows no chevron column and wires no
controller.

## Stylesheets, and which one to link

| File | Constant | Link it |
|---|---|---|
| `element.css` | `UhifadhiElementBundle::STYLESHEET` | on every page that draws a component |
| `element-tokens.css` | `UhifadhiElementBundle::TOKENS_STYLESHEET` | only where nothing else publishes the house `--c-*` channels |

The component sheet **spends** the house channels and defines none, so a page
that already has them links the first alone. Two definitions of one colour is a
rendering decided by load order — which is why the split exists and why a test
enforces it.

## Building the stylesheet

```console
composer css:build
```

That runs `bin/console tailwind:build` against `assets/styles/element.css` and
copies the result to `public/element.css`. `bin/console` boots a dev-only build
kernel; it is the maintainer's, not an installation's. **Edit the input file, run
the build, commit both** — CI asserts the committed output and will not rebuild
it, so a change that never ran the build fails loudly instead of shipping.

## The style guide

Every component, with sample rows, on one page: the `/style-guide` route mounted
by `tests/Integration/TestKernel.php`. It is what the functional suite reads and
what a maintainer opens in a browser. A component library nobody renders drifts.

## Configuration

```yaml
# config/packages/element.yaml (optional — create it only to override)
element:
    table:
        empty_text: 'Nothing here yet.'   # what a register says when it has no rows
```

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

AGPL-3.0-or-later: see the [LICENSE](LICENSE) file for more details.
