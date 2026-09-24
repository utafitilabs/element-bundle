# Element — documentation

A Twig component library for Symfony: components styled with Tailwind, themed
entirely from the consuming application's configuration.

## Contents

- [Start here](#start-here)
- [The pages](#the-pages)
- [How a component is added](#how-a-component-is-added)

## Start here

[The README](../README.md) is the whole of the install, the component reference
and the two lines an installation still owns. This directory holds what does not
belong in a README: the reasoning, and the open questions.

## The pages

| Page | What it answers |
|---|---|
| [design-decisions.md](design-decisions.md) | every deliberate choice, why, and the trigger that reopens it |

## How a component is added

1. **The design is settled first.** A component here is the port of a ruled
   design, not a new drawing. Designs are truth.
2. **Write the tests.** The render test that asserts the markup, and — where the
   component ships CSS — the conformance test that asserts the declarations
   against the BUILT stylesheet.
3. **The class**, in `src/Twig/Components/`, with any value objects it thinks in
   under `src/Model/`. Per-row and per-column decisions are prepared in
   `#[PostMount]`, because `this` refuses computed methods that take arguments.
4. **The template**, in `templates/components/`, controlling its whitespace —
   the markup is asserted literally.
5. **Tag it by hand** in `config/services.php`: `twig.component` with `key`,
   `template` and `expose_public_props: true`. A reusable bundle does not
   autoconfigure.
6. **The styles** go in `assets/styles/element.css`. They spend `var(--e-*)` and
   define nothing: a value the components need and the theme has not got is a new
   node in `element.theme`, not a literal in the sheet. Run `composer css:build`
   and commit the input and the output together.
7. **Add it to the style guide** (`tests/Integration/Fixtures/templates/style-guide.html.twig`).
   A component that cannot be looked at drifts.
8. **`composer check`** — code style, PHPStan max, the suite.
