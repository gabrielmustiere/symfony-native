<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Parcours d'ouverture de compte (démo vitrine, sans persistance).
 *
 * La coquille héberge le LiveComponent OnboardingFlow qui porte tout l'état.
 */
final class OnboardingController extends AbstractController
{
    #[Route(path: '/ouverture-de-compte', name: 'app_onboarding')]
    public function open(): Response
    {
        return $this->render('onboarding/index.html.twig');
    }
}
