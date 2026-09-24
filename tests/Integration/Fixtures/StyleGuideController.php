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

namespace UtafitiLabs\ElementBundle\Tests\Integration\Fixtures;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * THE STYLE GUIDE — every component this library ships, rendered with sample
 * rows, on one page.
 *
 * Without one, authors guess: a component library whose only rendering is
 * inside somebody else's application cannot be looked at, and what cannot be
 * looked at drifts. It is the functional suite's subject AND the page a
 * maintainer opens in a browser (`php -S` over the test kernel), which is the
 * point — a guide nobody renders is a document, not a guide.
 *
 * The sample data is synthetic and names nobody: berths on an invented
 * harbour's quays, with their depths and what is moored at them.
 *
 * Patterned on FrameworkBundle's own TemplateController: a controller extends
 * nothing and takes what it needs in its constructor.
 *
 * @see vendor/symfony/framework-bundle/Controller/TemplateController.php
 */
final class StyleGuideController
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function __invoke(Request $request): Response
    {
        $open = $request->query->get('open');

        return new Response($this->twig->render('@Fixtures/style-guide.html.twig', [
            'open' => \is_string($open) ? $open : '',
            'sort' => 'depth',
            'columns' => [
                ['key' => 'berth', 'label' => 'Berth', 'sortUrl' => '?sort=berth'],
                ['key' => 'quay', 'label' => 'Quay'],
                ['key' => 'depth', 'label' => 'Depth', 'sortUrl' => '?sort=depth', 'numeric' => true],
                ['key' => 'moored', 'label' => 'Moored'],
            ],
            'rows' => [
                [
                    'id' => 'north-12', 'name' => 'North 12', 'foldable' => true,
                    'cells' => ['berth' => 'North 12', 'quay' => 'North quay', 'depth' => '7', 'moored' => 'Kestrel'],
                ],
                [
                    'id' => 'east-7', 'name' => 'East 7', 'foldable' => true,
                    'cells' => ['berth' => 'East 7', 'quay' => 'Fish dock', 'depth' => '4', 'moored' => 'Petrel'],
                ],
                [
                    'id' => 'dry-dock', 'name' => 'Dry dock', 'foldable' => true,
                    'cells' => ['berth' => 'Dry dock', 'quay' => 'Inner basin', 'depth' => '11', 'moored' => 'Nothing'],
                ],
            ],
        ]));
    }
}
