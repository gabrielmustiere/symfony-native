<?php

declare(strict_types=1);

namespace App\Onboarding;

/**
 * Offre de compte de la démo d'onboarding — 100 % factice, aucune souscription réelle.
 */
final readonly class Offer
{
    /**
     * @param list<string> $perks avantages affichés sur la carte d'offre
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $tagline,
        public int $monthlyPriceCents,
        public array $perks,
        public string $icon,
        public bool $highlighted = false,
    ) {
    }
}
