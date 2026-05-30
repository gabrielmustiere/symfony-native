<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class WellKnownController extends AbstractController
{
    /**
     * Apple App Site Association : autorise l'app poc-mobile à utiliser les
     * passkeys WebAuthn (service `webcredentials`) depuis son WKWebView.
     *
     * Servi sans extension, en application/json, comme l'exige iOS.
     */
    #[Route(
        path: '/.well-known/apple-app-site-association',
        name: 'app_well_known_aasa',
        methods: ['GET']
    )]
    public function appleAppSiteAssociation(): JsonResponse
    {
        return new JsonResponse([
            'webcredentials' => [
                'apps' => ['2S4J753898.net.technao.poc-mobile'],
            ],
        ]);
    }
}
