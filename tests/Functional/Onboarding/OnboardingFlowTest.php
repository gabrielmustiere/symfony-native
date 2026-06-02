<?php

declare(strict_types=1);

namespace App\Tests\Functional\Onboarding;

use App\Twig\Components\OnboardingFlow;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;

/**
 * Couvre le LiveComponent du parcours : gating de validation par étape,
 * conservation d'état au retour arrière, « remplir un exemple », et flow
 * complet jusqu'au succès — sans aucune persistance.
 */
final class OnboardingFlowTest extends KernelTestCase
{
    use InteractsWithLiveComponents;

    /**
     * @return array<string, string>
     */
    private function validData(): array
    {
        return [
            'civility' => 'mme',
            'firstName' => 'Juliette',
            'lastName' => 'Lecomte',
            'birthDate' => '1990-04-12',
            'nationality' => 'FR',
            'email' => 'juliette.lecomte@example.fr',
            'phone' => '06 24 18 53 09',
            'addressLine' => '24 rue des Lilas',
            'postalCode' => '75011',
            'city' => 'Paris',
            'documentProvided' => '1',
            'offer' => 'confort',
            'consent' => '1',
        ];
    }

    private function flow(TestLiveComponent $component): OnboardingFlow
    {
        $flow = $component->component();
        self::assertInstanceOf(OnboardingFlow::class, $flow);

        return $flow;
    }

    public function testMountsOnFirstStep(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        self::assertSame(1, $this->flow($component)->step);
        self::assertStringContainsString('Faisons connaissance', (string) $component->render());
    }

    public function testNextBlocksWhenCurrentStepInvalid(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        // Étape ① vide → la validation échoue (422) et le parcours ne progresse pas.
        try {
            $component->call('next');
            self::fail('« Continuer » aurait dû échouer la validation de l\'étape ①.');
        } catch (UnprocessableEntityHttpException) {
            // Comportement attendu : gating par étape.
        }

        self::assertSame(1, $this->flow($component)->step);
    }

    public function testAgeGatingBlocksMinor(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        $minor = $this->validData();
        $minor['birthDate'] = (new \DateTimeImmutable('-10 years'))->format('Y-m-d');

        try {
            $component->submitForm(['onboarding' => $minor], 'next');
            self::fail('Un mineur ne devrait pas passer l\'étape identité.');
        } catch (UnprocessableEntityHttpException) {
            // Comportement attendu : contrainte d'âge ≥ 18 ans.
        }

        self::assertSame(1, $this->flow($component)->step);
    }

    public function testNextAdvancesWhenCurrentStepValid(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        $component->submitForm(['onboarding' => $this->validData()], 'next');

        self::assertSame(2, $this->flow($component)->step);
    }

    public function testPrevKeepsAlreadyEnteredData(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        $component->submitForm(['onboarding' => $this->validData()], 'next');
        self::assertSame(2, $this->flow($component)->step);

        $component->call('prev');

        self::assertSame(1, $this->flow($component)->step);
        // La saisie de l'étape ① est conservée (retour arrière non destructif).
        self::assertStringContainsString('Juliette', (string) $component->render());
    }

    public function testFillExamplePopulatesTheForm(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        $component->call('fillExample');

        self::assertStringContainsString('juliette.lecomte@example.fr', (string) $component->render());
    }

    public function testFullFlowReachesSuccessWithoutPersistence(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        // Renseigne tout l'écran ① et avance étape après étape.
        $component->submitForm(['onboarding' => $this->validData()], 'next'); // ① → ②
        self::assertSame(2, $this->flow($component)->step);

        $component->call('next'); // ② → ③
        self::assertSame(3, $this->flow($component)->step);

        $component->call('next'); // ③ → ④
        self::assertSame(4, $this->flow($component)->step);

        $component->call('submit'); // ④ → succès

        self::assertTrue($this->flow($component)->completed);
        self::assertStringContainsString('Bienvenue', (string) $component->render());
    }

    public function testRestartResetsTheJourney(): void
    {
        $component = $this->createLiveComponent('OnboardingFlow');

        $component->submitForm(['onboarding' => $this->validData()], 'next');
        $component->call('next');
        $component->call('next');
        $component->call('submit');
        self::assertTrue($this->flow($component)->completed);

        $component->call('restart');

        self::assertFalse($this->flow($component)->completed);
        self::assertSame(1, $this->flow($component)->step);
        // Données effacées après réinitialisation.
        self::assertStringNotContainsString('juliette.lecomte@example.fr', (string) $component->render());
    }
}
