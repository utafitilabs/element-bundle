# Design decisions

Every deliberate choice this bundle makes, why it was made, and **the trigger
that reopens it** — so nobody "fixes" a decision later without knowing it was
one.

## Contents

- [Open — for the kick-off](#open--for-the-kick-off)
- [The fold contract](#the-fold-contract)
- [Tailwind, built here and committed](#tailwind-built-here-and-committed)
- [The palette lives in two files](#the-palette-lives-in-two-files)
- [No dependency on an application](#no-dependency-on-the-platform)
- [Explicit DI, and no AsTwigComponent attribute](#explicit-di-and-no-astwigcomponent-attribute)
- [The component prepares the view](#the-component-prepares-the-view)
- [Rows and columns are value objects, built from arrays](#rows-and-columns-are-value-objects-built-from-arrays)
- [The chevron is an anchor](#the-chevron-is-an-anchor)

## Open — for the kick-off

**NEEDS A VERDICT: design-workspace parity — who owns the register vocabulary once
components live here?**

This library now ships `.c`, `.tab`, `.src`, `table.tbl`, the sortable-header
rules and the whole fold contract. `shell-module/public/shell.css` ships the same
rules today, and the design workspace
a consuming product keeps is the drawing both were ported from. Three copies of one vocabulary is two too many, and two of
them being loaded on the same page is a rendering decided by load order.

What the owner has to decide, before the shell consumes `<twig:Element:Table>`:

1. **Does the shell give the register vocabulary up?** If the shell keeps its
   copies, a shell page that draws this component loads both and the later sheet
   wins. The clean answer is that the shell deletes `table.tbl`, the fold rules,
   `.c` and `.tab` from `shell.css` in the same release that adopts the
   component. That is a coordinated change across two packages, so it is a
   ruling, not a refactor.
2. **Who owns the `--c-*` channels?** Today both `shell.css` and this bundle's
   `element-tokens.css` define them; this bundle's copy exists so the library can
   stand on its own and so its style guide renders. Either element becomes the
   home of the palette and the shell links it, or the shell stays the home and
   `element-tokens.css` is demoted to a style-guide-only file that no
   installation ever links. The code is written so either answer is a one-file
   change; the decision is not this bundle's to make.
3. **Is the design workspace still the source of truth, or is the style guide?**
   A component library with a rendered guide can be the thing a design is checked
   against. If the workspace stays canonical, the guide is a mirror and someone
   has to keep them in step; if the guide becomes canonical, the workspace's
   register pages retire. Today the workspace is canonical and this library was
   ported from it.

Until that verdict, nothing in an application consumes this bundle — which is
deliberate, and why this first version ships with no integration.

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

## The palette lives in two files

`element.css` spends `rgb(var(--c-*))` and defines nothing;
`element-tokens.css` defines the channels for a page that has no other source of
them. Two definitions of one colour is a rendering decided by load order, and
that is the bug the split prevents. Two tests hold it: the component sheet
defines no channel, and the token sheet defines every channel the component sheet
spends.

*Reopens when:* the ownership question above is ruled.

## No dependency on an application

`composer.json` requires no application package, and `tests/Integration/TestKernel.php`
installs no platform bundle. A component library an application is built out of
cannot be built out of an application, and a test kernel that quietly installed the
shell would hide the day this stopped being true.

*Reopens when:* a component genuinely needs a platform contract — in which case
the contract, not the implementation, is what this bundle may depend on.

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
