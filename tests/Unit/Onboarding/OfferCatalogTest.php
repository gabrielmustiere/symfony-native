<?php

declare(strict_types=1);

namespace App\Tests\Unit\Onboarding;

use App\Onboarding\OfferCatalog;
use PHPUnit\Framework\TestCase;

final class OfferCatalogTest extends TestCase
{
    public function testExposesThreeCoherentOffers(): void
    {
        $offers = (new OfferCatalog())->offers();

        self::assertCount(3, $offers);

        foreach ($offers as $offer) {
            self::assertNotSame('', $offer->slug);
            self::assertNotSame('', $offer->name);
            self::assertNotSame('', $offer->tagline);
            self::assertGreaterThanOrEqual(0, $offer->monthlyPriceCents);
            self::assertNotEmpty($offer->perks, 'Chaque offre liste au moins un avantage.');
        }
    }

    public function testSlugsAreUniqueAndExposed(): void
    {
        $catalog = new OfferCatalog();
        $slugs = $catalog->slugs();

        self::assertSame(['essentiel', 'confort', 'premium'], $slugs);
        self::assertSame($slugs, array_unique($slugs));
    }

    public function testOfferLookupBySlug(): void
    {
        $catalog = new OfferCatalog();

        $confort = $catalog->offer('confort');
        self::assertNotNull($confort);
        self::assertSame('Compte Confort', $confort->name);
        self::assertTrue($confort->highlighted);
        self::assertNull($catalog->offer('inconnu'));
    }

    public function testExampleDataIsPlausibleAndValid(): void
    {
        $catalog = new OfferCatalog();
        $example = $catalog->exampleData();

        // Identité et coordonnées renseignées.
        self::assertNotNull($example->civility);
        self::assertNotSame('', (string) $example->firstName);
        self::assertNotSame('', (string) $example->lastName);
        self::assertNotNull($example->birthDate);
        self::assertNotSame('', (string) $example->email);

        // Pièce « déposée », offre valide, consentement laissé au présentateur.
        self::assertTrue($example->documentProvided);
        self::assertContains($example->offer, $catalog->slugs());
        self::assertFalse($example->consent);

        // Le titulaire d'exemple est majeur.
        $eighteenYearsAgo = new \DateTimeImmutable('-18 years');
        self::assertLessThanOrEqual($eighteenYearsAgo, $example->birthDate);
    }
}
