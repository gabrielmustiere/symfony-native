<?php

declare(strict_types=1);

namespace App\Bank;

use App\Enum\Type\TransactionCategory;

/**
 * Source de données 100 % factice pour la démo commerciale « Nevertime Solutions ».
 *
 * Aucune persistance, aucune logique bancaire réelle : uniquement des données
 * crédibles servant l'effet de démonstration (cf. docs/vision.md).
 */
final class DemoBankProvider
{
    public const string BANK_NAME = 'Nevertime Solutions';

    public function customer(): Customer
    {
        return new Customer(
            firstName: 'Camille',
            lastName: 'Moreau',
            email: 'camille.moreau@nevertime.fr',
            phone: '+33 6 24 18 53 09',
            customerNumber: 'NS-2049-7731',
            memberSince: new \DateTimeImmutable('2018-09-14'),
        );
    }

    /**
     * @return list<Account>
     */
    public function accounts(): array
    {
        return [
            new Account(
                id: 'courant',
                name: 'Compte courant',
                type: 'Compte individuel',
                iban: 'FR76 3000 4008 2800 0124 5821 094',
                balanceCents: 482657,
                icon: 'tabler:wallet',
                transactions: $this->currentAccountTransactions(),
            ),
            new Account(
                id: 'livret-a',
                name: 'Livret A',
                type: 'Épargne réglementée',
                iban: 'FR76 3000 4008 2800 0398 7712 045',
                balanceCents: 1240000,
                icon: 'tabler:pig-money',
                transactions: $this->savingsTransactions(),
            ),
            new Account(
                id: 'joint',
                name: 'Compte joint',
                type: 'Camille & Théo Moreau',
                iban: 'FR76 3000 4008 2800 0512 3390 188',
                balanceCents: 231042,
                icon: 'tabler:users',
                transactions: $this->jointAccountTransactions(),
            ),
            new Account(
                id: 'assurance-vie',
                name: 'Assurance vie',
                type: 'Contrat Sérénité',
                iban: 'FR76 3000 4008 2800 0741 6628 277',
                balanceCents: 2875000,
                icon: 'tabler:chart-line',
                transactions: $this->lifeInsuranceTransactions(),
            ),
        ];
    }

    public function account(string $id): ?Account
    {
        foreach ($this->accounts() as $account) {
            if ($account->id === $id) {
                return $account;
            }
        }

        return null;
    }

    public function totalBalanceCents(): int
    {
        $total = 0;
        foreach ($this->accounts() as $account) {
            $total += $account->balanceCents;
        }

        return $total;
    }

    /**
     * @return list<Transaction>
     */
    private function currentAccountTransactions(): array
    {
        $recent = [
            new Transaction('Maison Léon — Boulangerie', -860, new \DateTimeImmutable('2026-05-30'), TransactionCategory::Restaurant),
            new Transaction('Franprix', -3421, new \DateTimeImmutable('2026-05-30'), TransactionCategory::Groceries),
            new Transaction('Netflix', -1349, new \DateTimeImmutable('2026-05-29'), TransactionCategory::Subscription),
            new Transaction('Uber Eats', -2780, new \DateTimeImmutable('2026-05-28'), TransactionCategory::Restaurant),
            new Transaction('SNCF Connect', -8990, new \DateTimeImmutable('2026-05-27'), TransactionCategory::Transport),
            new Transaction('Retrait DAB — République', -6000, new \DateTimeImmutable('2026-05-26'), TransactionCategory::Withdrawal),
            new Transaction('Pharmacie du Centre', -2315, new \DateTimeImmutable('2026-05-25'), TransactionCategory::Health),
            new Transaction('Virement vers Livret A', -30000, new \DateTimeImmutable('2026-05-24'), TransactionCategory::Transfer),
            new Transaction('Carrefour Market', -6842, new \DateTimeImmutable('2026-05-23'), TransactionCategory::Groceries),
            new Transaction('TotalEnergies', -7130, new \DateTimeImmutable('2026-05-22'), TransactionCategory::Transport),
            new Transaction('Spotify', -1099, new \DateTimeImmutable('2026-05-21'), TransactionCategory::Subscription),
            new Transaction('Le Bistrot Parisien', -4650, new \DateTimeImmutable('2026-05-20'), TransactionCategory::Restaurant),
            new Transaction('Decathlon', -5490, new \DateTimeImmutable('2026-05-19'), TransactionCategory::Shopping),
            new Transaction('Free Mobile', -1999, new \DateTimeImmutable('2026-05-18'), TransactionCategory::Subscription),
            new Transaction('EDF — Électricité', -8420, new \DateTimeImmutable('2026-05-16'), TransactionCategory::Housing),
            new Transaction('Amazon', -4299, new \DateTimeImmutable('2026-05-15'), TransactionCategory::Shopping),
            new Transaction('Virement salaire — TECHNAO SAS', 328000, new \DateTimeImmutable('2026-05-02'), TransactionCategory::Income),
            new Transaction('Loyer — SCI Bellevue', -115000, new \DateTimeImmutable('2026-05-02'), TransactionCategory::Housing),
        ];

        return $recent;
    }

    /**
     * @return list<Transaction>
     */
    private function savingsTransactions(): array
    {
        return [
            new Transaction('Virement depuis Compte courant', 30000, new \DateTimeImmutable('2026-05-24'), TransactionCategory::Transfer),
            new Transaction('Virement depuis Compte courant', 30000, new \DateTimeImmutable('2026-04-23'), TransactionCategory::Transfer),
            new Transaction('Virement depuis Compte courant', 30000, new \DateTimeImmutable('2026-03-24'), TransactionCategory::Transfer),
            new Transaction('Intérêts annuels', 24800, new \DateTimeImmutable('2025-12-31'), TransactionCategory::Income),
        ];
    }

    /**
     * @return list<Transaction>
     */
    private function jointAccountTransactions(): array
    {
        return [
            new Transaction('Picard Surgelés', -4780, new \DateTimeImmutable('2026-05-29'), TransactionCategory::Groceries),
            new Transaction('Cinéma UGC', -2900, new \DateTimeImmutable('2026-05-27'), TransactionCategory::Leisure),
            new Transaction('Virement Camille Moreau', 50000, new \DateTimeImmutable('2026-05-25'), TransactionCategory::Transfer),
            new Transaction('Virement Théo Moreau', 50000, new \DateTimeImmutable('2026-05-25'), TransactionCategory::Transfer),
            new Transaction('IKEA', -18940, new \DateTimeImmutable('2026-05-18'), TransactionCategory::Shopping),
            new Transaction('Veolia — Eau', -3612, new \DateTimeImmutable('2026-05-12'), TransactionCategory::Housing),
        ];
    }

    /**
     * @return list<Transaction>
     */
    private function lifeInsuranceTransactions(): array
    {
        return [
            new Transaction('Versement programmé', 20000, new \DateTimeImmutable('2026-05-05'), TransactionCategory::Transfer),
            new Transaction('Versement programmé', 20000, new \DateTimeImmutable('2026-04-06'), TransactionCategory::Transfer),
            new Transaction('Participation aux bénéfices', 41280, new \DateTimeImmutable('2025-12-31'), TransactionCategory::Income),
        ];
    }
}
