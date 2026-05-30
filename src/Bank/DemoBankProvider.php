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

    /** Volume total d'opérations du compte courant (matière pour la recherche). */
    private const int CURRENT_ACCOUNT_HISTORY = 2000;

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

        return array_merge($recent, $this->generatedHistory(self::CURRENT_ACCOUNT_HISTORY - \count($recent)));
    }

    /**
     * Historique factice généré de façon déterministe (du plus récent au plus ancien),
     * pour donner de la matière à la recherche/filtre — aucune donnée réelle.
     *
     * @return list<Transaction>
     */
    private function generatedHistory(int $count): array
    {
        /** @var list<array{string, TransactionCategory, int, int}> $merchants */
        $merchants = [
            ['Carrefour Market', TransactionCategory::Groceries, 2500, 6000],
            ['Franprix', TransactionCategory::Groceries, 1500, 4000],
            ['Monoprix', TransactionCategory::Groceries, 2200, 5000],
            ['Picard Surgelés', TransactionCategory::Groceries, 1800, 3000],
            ['Maison Léon — Boulangerie', TransactionCategory::Restaurant, 400, 1200],
            ['Le Bistrot Parisien', TransactionCategory::Restaurant, 2400, 4000],
            ['Uber Eats', TransactionCategory::Restaurant, 1500, 2500],
            ['Starbucks', TransactionCategory::Restaurant, 500, 800],
            ["McDonald's", TransactionCategory::Restaurant, 900, 1500],
            ['SNCF Connect', TransactionCategory::Transport, 3500, 9000],
            ['TotalEnergies', TransactionCategory::Transport, 5000, 4000],
            ['RATP — Navigo', TransactionCategory::Transport, 8650, 100],
            ['Uber', TransactionCategory::Transport, 900, 2500],
            ['Netflix', TransactionCategory::Subscription, 1349, 1],
            ['Spotify', TransactionCategory::Subscription, 1099, 1],
            ['Free Mobile', TransactionCategory::Subscription, 1999, 1],
            ['Canal+', TransactionCategory::Subscription, 2490, 1],
            ['Amazon', TransactionCategory::Shopping, 1500, 8000],
            ['Fnac', TransactionCategory::Shopping, 2000, 7000],
            ['Decathlon', TransactionCategory::Shopping, 2500, 6000],
            ['Zara', TransactionCategory::Shopping, 3000, 5000],
            ['IKEA', TransactionCategory::Shopping, 4000, 12000],
            ['Apple Store', TransactionCategory::Shopping, 999, 30000],
            ['Pharmacie du Centre', TransactionCategory::Health, 800, 3000],
            ['Cabinet médical', TransactionCategory::Health, 2500, 3000],
            ['EDF — Électricité', TransactionCategory::Housing, 6000, 5000],
            ['Veolia — Eau', TransactionCategory::Housing, 3000, 2000],
            ['Cinéma UGC', TransactionCategory::Leisure, 1200, 1500],
            ['Steam', TransactionCategory::Leisure, 1999, 4000],
            ['Retrait DAB', TransactionCategory::Withdrawal, 4000, 6000],
        ];

        $transactions = [];
        $date = new \DateTimeImmutable('2026-05-01');
        $merchantCount = \count($merchants);

        for ($i = 0; $i < $count; ++$i) {
            if (0 === $i % 2) {
                $date = $date->modify('-1 day');
            }

            if (0 === $i % 62) {
                $transactions[] = new Transaction('Virement salaire — TECHNAO SAS', 328000, $date, TransactionCategory::Income);
                continue;
            }

            if (0 === $i % 49) {
                $transactions[] = new Transaction('Remboursement Ameli', 1500 + ($i * 7) % 4000, $date, TransactionCategory::Income);
                continue;
            }

            [$label, $category, $base, $spread] = $merchants[($i * 7) % $merchantCount];
            $amount = $base + ($i * 131) % ($spread + 1);
            $transactions[] = new Transaction($label, -$amount, $date, $category);
        }

        return $transactions;
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
