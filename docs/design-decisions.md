# Design decisions

Every deliberate choice this bundle makes, why it was made, and **the trigger
that reopens it** — so nobody "fixes" a decision later without knowing it was
one.

## Contents

- [A generic bundle, themed from configuration](#a-generic-bundle-themed-from-configuration)
- [The categorical hues](#the-categorical-hues)
- [Where truth lives](#where-truth-lives)
- [The fold contract](#the-fold-contract)
- [Tailwind, built here and committed](#tailwind-built-here-and-committed)
- [No dependency on an application](#no-dependency-on-an-application)
- [Explicit DI, and no AsTwigComponent attribute](#explicit-di-and-no-astwigcomponent-attribute)
- [The component prepares the view](#the-component-prepares-the-view)
- [Rows and columns are value objects, built from arrays](#rows-and-columns-are-value-objects-built-from-arrays)
- [The chevron is an anchor](#the-chevron-is-an-anchor)

## A generic bundle, themed from configuration

**RULED 2026-09-24.** This is a generic library: any product installs it
unchanged, and products differ by **colour and type only** — so the flexibility
lives in configuration, never in a fork of the sheet.

The consequences, all of them deliberate:

- The built stylesheet spends `var(--e-*)` and **defines nothing**. A value it
  needs and the theme has not got is a new node in `element.theme`, not a literal.
- There is **no token stylesheet**. `element_theme()` renders the values into the
  page, which ends the old failure mode of two sheets defining one colour and the
  rendering being decided by load order. It also means there is nothing for a
  product to "install first".
- The properties carry this bundle's own prefix (`--e-`), so a page that also
  carries somebody else's design tokens cannot collide with these.
- A theme value goes into a `<style>` element, so the characters that could close
  one are refused **when the container is built** — a validation error an
  installation reads, not an escape a reader never sees.

**This bundle owns the register vocabulary** — `.c`, `.tab`, `.src`, `table.tbl`,
the sortable-header rules and the fold contract — and the palette channels. A
product that has its own copies of those rules deletes them in the release that
adopts the component; two copies on one page is a rendering decided by load
order.

*Reopens when:* a component genuinely cannot be expressed as one sheet plus
values — at which point the answer is a new configuration node, and only then a
second sheet.

## The categorical hues

**RULED 2026-09-24: categorical hues are the bundle's, named by look; the product
maps its categories.** Eighteen colours ship under `element.theme.hues`, named for
what they look like (`moss`, `ochre`, `sky`, …) because what they MEAN belongs to
the product: a product maps its own categories onto them, by NAME where a meaning
has to stay put and by INDEX (`--e-hue-7`) where a series just needs colours that
differ. The library never learns what a category is, so no product word reaches
this repository.

The set is **open** — a product restates any value and appends its own, and both
are the same gesture — and it is **measured, not chosen by eye**: every hue stands
at least 12.0 from every other in CIE76 Lab distance (the closest shipped pair
measures 13.3) and clears 4.5:1 against the ground it is drawn on, in light and
in dark. `tests/Unit/Theme/HuePaletteTest.php` asserts both, so the day somebody
adds a nineteenth the suite says whether the set can still be told apart. CIE76
is the simple metric on purpose: the number this bundle computes and the number a
product computes over its own additions are arrived at the same way.

*Reopens when:* a set this size stops being enough, which is a request for a
second dimension (weight, pattern) rather than a nineteenth hue.

## Where truth lives

**RULED 2026-09-24.** Component-level truth is **this bundle's rendered style
guide**: what a register looks like is settled here, and a product checks its
port against the guide. Page-level truth stays with **the consuming product's own
design workspace**: how a screen is composed, what goes where, and which
components a page wears is the product's drawing, not the library's.

*Reopens when:* a product needs a component the guide does not render — the
component is designed in that product's workspace and then moves here, guide
first.

## The fold contract

**Ruled 2026-09-22, ported verbatim, not restyled.** The declarations, the
600ms `cubic-bezier(.4, 0, .2, 1)` curve, the dashed rule the fold row carries in
both states, and the margin-not-padding spacing are all reproduced exactly as
ruled. `tests/Unit/Stylesheet/FoldContractTest.php` asserts each of them against
the BUILT stylesheet, value by value.

*Reopens when:* the owner rules a different motion. Not when a build tool
reformats — the test collapses whitespace precisely so formatting is not mistaken
for a change of contract.

*Known wrinkle:* Tailwind rewrites every `color-mix()` declaration into a plain
fallback plus an `@supports` block. The values survive verbatim; the braces
around them do not, so the fold row's rule is asserted declaration by declaration
rather than as one block.

## Tailwind, built here and committed

A reusable bundle cannot run an installation's asset pipeline, so `public/element.css`
is a committed build output and `composer css:build` is the maintainer's step.
CI does **not** rebuild it: the committed sheet is a reviewed artefact, and a
change that never ran the build must fail rather than ship.

Preflight is not imported — Tailwind's reset is a page-wide opinion and this
library loads inside somebody else's page. Source scanning is off
(`source(none)`): with it on, the word `block` in a Twig `{% block %}` shipped a
`.block` utility to every consumer. A host that wants utilities runs its own
Tailwind build over its own templates and imports this theme, which is why the
theme is emitted whole (`@theme static`).

*Reopens when:* an application gains a shared asset build that bundles can hook
into, or when a consumer needs utilities this library cannot know about.

## No dependency on an application

`composer.json` requires no application package, and `tests/Integration/TestKernel.php`
installs no application bundle. A component library an application is built out
of cannot be built out of the application, and a test kernel that quietly
installed one would hide the day this stopped being true.

*Reopens when:* a component genuinely needs a contract from its host — in which
case the contract, not an implementation of it, is what this bundle may depend
on.

## Explicit DI, and no AsTwigComponent attribute

A reusable bundle does not autoconfigure, so the attribute's tag would never be
applied; `config/services.php` applies `twig.component` by hand with the same
key and template. **`expose_public_props` must be stated explicitly** —
`ComponentMetadata` defaults it to `false` when absent, and the autoconfiguration
callback `array_filter()`s the attribute's config, so the attribute's own `true`
is never written down. Without that line every prop of every component is
invisible to its own template.

## The component prepares the view

TwigComponent's `this` proxy refuses a computed method that takes arguments, and
every per-row and per-column question this component answers takes one. So
`postMount()` works the answers out once into `headers` and `body`, and the
template iterates them. The decisions stay unit-tested and the template stays a
view.

*Reopens when:* the proxy grows argument support.

## Rows and columns are value objects, built from arrays

A caller holding value objects passes them through; a caller writing a Twig
literal writes `{ key: …, label: … }`. `#[PreMount]` turns the second into the
first, so exactly one shape reaches the template. A column names the **fact**
(`numeric: true`), never the class — so a depth is right-aligned and mono in
every register in the product without anyone remembering `.num`.

A **foldable row without an `id` is refused**, because the open set rides in the
address and such a row would shut on every reload with nobody told why.

## The chevron is an anchor

The rule is that interactive chrome is a real `<button>`, because Stimulus
binds no default event to `<b>` or `<span>`. The ruled design and the shipped
shell controller both use `<a class="fchev" href="#">`, which is a real
interactive element with keyboard behaviour, so the port keeps it rather than
inventing a difference.

*Reopens when:* the register vocabulary is reviewed for keyboard and screen
reader behaviour as a whole — at which point `<button>` plus `aria-controls` on
the fold row is the likelier answer.
