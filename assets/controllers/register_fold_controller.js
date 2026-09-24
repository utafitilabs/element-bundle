import { Controller } from '@hotwired/stimulus';

/*
 * A REGISTER ROW THAT FOLDS OPEN (ruled 2026-09-22 — the fold contract).
 *
 * The server renders a folding row followed by ONE `tr.foldrow` holding
 * `.foldbox > .fold-in > content`, open where the address names it. This
 * controller owns nothing but the click: it toggles `open` on the row and its
 * fold row (the stylesheet animates the track), turns the chevron's state, and
 * writes the set of open rows back into the address — `?open=a,b` — so the page
 * a reader is looking at is the page they can send. NO STATE LIVES HERE, and
 * none is kept in the browser: the address is the whole of the memory, which is
 * why a reload reopens exactly what was open and a shared link does too.
 *
 * Identifier in a host: `utafitilabs--element-bundle--register-fold` — StimulusBundle's
 * own normalisation of this package's asset namespace plus the name
 * assets/package.json publishes the controller under. The component template
 * writes it from UtafitiLabs\ElementBundle\UtafitiLabsElementBundle::FOLD_CONTROLLER, and
 * tests/Unit/Asset/FoldControllerSeamTest.php holds the three spellings
 * together.
 *
 * @see https://symfony.com/bundles/StimulusBundle/current/index.html
 */
export default class extends Controller {
    static values = { param: { type: String, default: 'open' } };

    toggle(event) {
        event.preventDefault();

        const chevron = event.currentTarget;
        const row = chevron.closest('tr');
        const fold = row?.nextElementSibling;
        if (!fold || !fold.classList.contains('foldrow')) {
            return;
        }

        const open = !row.classList.contains('open');
        row.classList.toggle('open', open);
        fold.classList.toggle('open', open);
        chevron.setAttribute('aria-expanded', open ? 'true' : 'false');
        chevron.setAttribute('aria-label', ((open ? 'Collapse ' : 'Expand ') + (row.dataset.foldName ?? '')).trim());

        this.#remember();
    }

    #remember() {
        const ids = Array.from(this.element.querySelectorAll('tr.open[data-fold-id]')).map((r) => r.dataset.foldId);
        const url = new URL(window.location.href);

        if (ids.length) {
            url.searchParams.set(this.paramValue, ids.join(','));
        } else {
            url.searchParams.delete(this.paramValue);
        }

        window.history.replaceState(null, '', url);
    }
}
