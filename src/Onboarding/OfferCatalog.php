<?php

declare(strict_types=1);

namespace App\Onboarding;

use App\Enum\Type\Civility;

/**
 * Catalogue des offres de compte de la démo et jeu de données « remplir un exemple ».
 *
 * 100 % factice (cf. DemoBankProvider) : aucune souscription, aucune persistance.
 */
final class OfferCatalog
{
    /**
     * @return list<Offer>
     */
    public function offers(): array
    {
        return [
            new Offer(
                slug: 'essentiel',
                name: 'Compte Essentiel',
                tagline: 'L\'essentiel du quotidien, sans frais.',
                monthlyPriceCents: 0,
                perks: [
                    'Carte Visa à débit immédiat',
                    'Virements et prélèvements SEPA illimités',
                    'Application mobile et support 6j/7',
                ],
                icon: 'tabler:wallet',
            ),
            new Offer(
                slug: 'confort',
                name: 'Compte Confort',
                tagline: 'Plus de services pour gérer sereinement.',
                monthlyPriceCents: 490,
                perks: [
                    'Carte Visa Premier à débit immédiat ou différé',
                    'Assurances voyage et achats incluses',
                    'Plafonds de paiement personnalisables',
                    'Conseiller dédié',
                ],
                icon: 'tabler:diamond',
                highlighted: true,
            ),
            new Offer(
                slug: 'premium',
                name: 'Compte Premium',
                tagline: 'L\'accompagnement haut de gamme.',
                monthlyPriceCents: 1290,
                perks: [
                    'Carte Visa Infinite à débit différé',
                    'Assurances et assistance étendues dans le monde entier',
                    'Accès aux salons d\'aéroport',
                    'Gestion de patrimoine sur mesure',
                ],
                icon: 'tabler:crown',
            ),
        ];
    }

    public function offer(string $slug): ?Offer
    {
        foreach ($this->offers() as $offer) {
            if ($offer->slug === $slug) {
                return $offer;
            }
        }

        return null;
    }

    /**
     * Slugs valides, pour contraindre le champ « offre » du formulaire.
     *
     * @return list<string>
     */
    public function slugs(): array
    {
        return array_map(static fn (Offer $offer): string => $offer->slug, $this->offers());
    }

    /**
     * Jeu de données plausible pour le bouton « remplir un exemple » d'une démo live.
     *
     * Renseigne l'identité, les coordonnées, la pièce d'identité (déjà « déposée »)
     * et une offre ; laisse le consentement final à la charge du présentateur.
     */
    public function exampleData(): OnboardingData
    {
        $data = new OnboardingData();
        $data->civility = Civility::Madame;
        $data->lastName = 'Lecomte';
        $data->firstName = 'Juliette';
        $data->birthDate = new \DateTimeImmutable('1990-04-12');
        $data->nationality = 'FR';
        $data->email = 'juliette.lecomte@example.fr';
        $data->phone = '06 24 18 53 09';
        $data->addressLine = '24 rue des Lilas';
        $data->postalCode = '75011';
        $data->city = 'Paris';
        $data->documentProvided = true;
        $data->offer = 'confort';
        $data->consent = false;

        return $data;
    }
}
