<?php

declare(strict_types=1);

namespace App\Tests\Functional\Onboarding;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OnboardingPageTest extends WebTestCase
{
    public function testRouteIsPublicAndRendersFirstStep(): void
    {
        // Asset vitrine : accessible sans authentification.
        $client = static::createClient();
        $client->request('GET', '/ouverture-de-compte');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Faisons connaissance');
    }

    public function testLoginPageExposesOnboardingCta(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[data-test="login-open-account"]');
        self::assertSelectorExists('a[href="/ouverture-de-compte"]');
    }

    public function testHomeExposesCtaToOnboarding(): void
    {
        $client = static::createClient();
        $repository = static::getContainer()->get(UserRepository::class);
        self::assertInstanceOf(UserRepository::class, $repository);

        $user = $repository->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($user, 'La fixture admin@example.com doit être chargée en base de test.');
        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[data-test="open-account-cta"]');
    }
}
