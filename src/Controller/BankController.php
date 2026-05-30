<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bank\DemoBankProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BankController extends AbstractController
{
    public function __construct(
        private readonly DemoBankProvider $bank,
    ) {
    }

    #[Route(path: '/', name: 'app_bank_home')]
    public function home(): Response
    {
        return $this->render('bank/home.html.twig', [
            'customer' => $this->bank->customer(),
            'accounts' => $this->bank->accounts(),
            'total' => $this->bank->totalBalanceCents(),
        ]);
    }

    #[Route(path: '/comptes/{id}', name: 'app_bank_account')]
    public function account(string $id): Response
    {
        $account = $this->bank->account($id);
        if (null === $account) {
            throw $this->createNotFoundException('Compte introuvable.');
        }

        return $this->render('bank/account.html.twig', [
            'account' => $account,
        ]);
    }

    #[Route(path: '/cartes', name: 'app_bank_cards')]
    public function cards(): Response
    {
        return $this->render('bank/cards.html.twig', [
            'customer' => $this->bank->customer(),
        ]);
    }

    #[Route(path: '/profil', name: 'app_bank_profile')]
    public function profile(): Response
    {
        return $this->render('bank/profile.html.twig', [
            'customer' => $this->bank->customer(),
        ]);
    }
}
