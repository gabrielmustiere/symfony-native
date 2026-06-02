<?php

declare(strict_types=1);

namespace App\Bank;

use App\Enum\Type\TransactionCategory;

/**
 * Catalogue de marchands factices alimentant la génération d'historique.
 *
 * Reprend les enseignes historiques de la démo et les décline par emplacement
 * (ville/quartier) de façon déterministe afin d'exposer 500+ libellés distincts —
 * matière réaliste pour la recherche full-text. Aucune donnée réelle.
 *
 * @phpstan-type Merchant array{0: string, 1: TransactionCategory, 2: int, 3: int}
 */
final class MerchantCatalog
{
    /**
     * Enseignes de base : [libellé, catégorie, montant min en centimes, amplitude].
     *
     * @var list<Merchant>
     */
    private const array BRANDS = [
        ['Carrefour Market', TransactionCategory::Groceries, 2500, 6000],
        ['Franprix', TransactionCategory::Groceries, 1500, 4000],
        ['Monoprix', TransactionCategory::Groceries, 2200, 5000],
        ['Picard Surgelés', TransactionCategory::Groceries, 1800, 3000],
        ['Intermarché', TransactionCategory::Groceries, 2300, 5500],
        ['Lidl', TransactionCategory::Groceries, 1200, 4500],
        ['Auchan', TransactionCategory::Groceries, 2800, 7000],
        ['Naturalia', TransactionCategory::Groceries, 1600, 3500],
        ['Maison Léon — Boulangerie', TransactionCategory::Restaurant, 400, 1200],
        ['Le Bistrot Parisien', TransactionCategory::Restaurant, 2400, 4000],
        ['Uber Eats', TransactionCategory::Restaurant, 1500, 2500],
        ['Starbucks', TransactionCategory::Restaurant, 500, 800],
        ["McDonald's", TransactionCategory::Restaurant, 900, 1500],
        ['Deliveroo', TransactionCategory::Restaurant, 1400, 2800],
        ['Brasserie du Marché', TransactionCategory::Restaurant, 1900, 3600],
        ['Sushi Shop', TransactionCategory::Restaurant, 1700, 3200],
        ['SNCF Connect', TransactionCategory::Transport, 3500, 9000],
        ['TotalEnergies', TransactionCategory::Transport, 5000, 4000],
        ['RATP — Navigo', TransactionCategory::Transport, 8650, 100],
        ['Uber', TransactionCategory::Transport, 900, 2500],
        ['BlaBlaCar', TransactionCategory::Transport, 1500, 4000],
        ['Station Esso', TransactionCategory::Transport, 4500, 5000],
        ['Parking Indigo', TransactionCategory::Transport, 600, 2000],
        ['Netflix', TransactionCategory::Subscription, 1349, 1],
        ['Spotify', TransactionCategory::Subscription, 1099, 1],
        ['Free Mobile', TransactionCategory::Subscription, 1999, 1],
        ['Canal+', TransactionCategory::Subscription, 2490, 1],
        ['Disney+', TransactionCategory::Subscription, 1199, 1],
        ['Amazon Prime', TransactionCategory::Subscription, 699, 1],
        ['Deezer', TransactionCategory::Subscription, 1099, 1],
        ['Amazon', TransactionCategory::Shopping, 1500, 8000],
        ['Fnac', TransactionCategory::Shopping, 2000, 7000],
        ['Decathlon', TransactionCategory::Shopping, 2500, 6000],
        ['Zara', TransactionCategory::Shopping, 3000, 5000],
        ['IKEA', TransactionCategory::Shopping, 4000, 12000],
        ['Apple Store', TransactionCategory::Shopping, 999, 30000],
        ['Sephora', TransactionCategory::Shopping, 2200, 6500],
        ['Leroy Merlin', TransactionCategory::Shopping, 1800, 9000],
        ['Pharmacie du Centre', TransactionCategory::Health, 800, 3000],
        ['Cabinet médical', TransactionCategory::Health, 2500, 3000],
        ['Laboratoire Biogroup', TransactionCategory::Health, 1800, 4000],
        ['Optique Krys', TransactionCategory::Health, 4900, 8000],
        ['EDF — Électricité', TransactionCategory::Housing, 6000, 5000],
        ['Veolia — Eau', TransactionCategory::Housing, 3000, 2000],
        ['Engie — Gaz', TransactionCategory::Housing, 4500, 3500],
        ['Cinéma UGC', TransactionCategory::Leisure, 1200, 1500],
        ['Steam', TransactionCategory::Leisure, 1999, 4000],
        ['Fitness Park', TransactionCategory::Leisure, 2990, 1],
        ['FNAC Spectacles', TransactionCategory::Leisure, 3500, 9000],
        ['Retrait DAB', TransactionCategory::Withdrawal, 4000, 6000],
    ];

    /**
     * Emplacements appliqués aux enseignes pour démultiplier les libellés.
     *
     * @var list<string>
     */
    private const array LOCATIONS = [
        'Paris République',
        'Paris Bastille',
        'Lyon Part-Dieu',
        'Marseille Vieux-Port',
        'Bordeaux Saint-Jean',
        'Lille Centre',
        'Nantes Commerce',
        'Toulouse Capitole',
        'Strasbourg Krutenau',
        'Rennes République',
        'Montpellier Comédie',
        'Nice Masséna',
    ];

    /**
     * Catalogue complet aplati : enseignes de base + déclinaisons par emplacement.
     *
     * @return list<Merchant>
     */
    public function merchants(): array
    {
        $merchants = self::BRANDS;

        foreach (self::BRANDS as [$label, $category, $base, $spread]) {
            foreach (self::LOCATIONS as $location) {
                $merchants[] = [$label . ' — ' . $location, $category, $base, $spread];
            }
        }

        return $merchants;
    }
}
