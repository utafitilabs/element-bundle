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

namespace Uhifadhi\Element\Tests\Integration\Fixtures;

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
 * The sample data is synthetic and names nobody: positions in an invented
 * conservation authority, with seat counts and no people in them.
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
            'sort' => 'seats',
            'columns' => [
                ['key' => 'position', 'label' => 'Position', 'sortUrl' => '?sort=position'],
                ['key' => 'placement', 'label' => 'Placement'],
                ['key' => 'seats', 'label' => 'Seats', 'sortUrl' => '?sort=seats', 'numeric' => true],
                ['key' => 'grants', 'label' => 'Grants'],
            ],
            'rows' => [
                [
                    'id' => 'warden', 'name' => 'Warden', 'foldable' => true,
                    'cells' => ['position' => 'Warden', 'placement' => 'Organization', 'seats' => '3', 'grants' => 'Areas, Team'],
                ],
                [
                    'id' => 'ranger', 'name' => 'Ranger', 'foldable' => true,
                    'cells' => ['position' => 'Ranger', 'placement' => 'Area', 'seats' => '24', 'grants' => 'Patrols'],
                ],
                [
                    'id' => 'ecologist', 'name' => 'Ecologist', 'foldable' => true,
                    'cells' => ['position' => 'Ecologist', 'placement' => 'Department', 'seats' => '4', 'grants' => 'Grants nothing'],
                ],
            ],
        ]));
    }
}
