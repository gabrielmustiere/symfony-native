<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Form\OnboardingType;
use App\Onboarding\OfferCatalog;
use App\Onboarding\OnboardingData;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Wizard d'onboarding bancaire (démo vitrine).
 *
 * Porte l'étape courante et délègue l'état des champs à `ComponentWithFormTrait`
 * (les valeurs vivent dans la LiveProp `formValues`, ce qui conserve nativement
 * la saisie entre étapes et au retour arrière — aucune persistance).
 *
 * Le gating de validation par étape repose sur `submitForm()` : il valide le
 * groupe de l'étape courante (via l'option `current_step` du Form) et lève une
 * 422 si l'étape est invalide, ce qui empêche l'avancement.
 */
#[AsLiveComponent]
final class OnboardingFlow
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    public const int FIRST_STEP = 1;
    public const int LAST_STEP = 4;

    #[LiveProp]
    public int $step = self::FIRST_STEP;

    #[LiveProp]
    public bool $completed = false;

    /** Étape ③ : analyse factice de la pièce en cours (piloté serveur, animé via Stimulus). */
    #[LiveProp]
    public bool $analyzing = false;

    /** Données du Form ; transitoire (la persistance d'état passe par `formValues`). */
    private OnboardingData $data;

    public function __construct(
        private readonly OfferCatalog $catalog,
        private readonly FormFactoryInterface $formFactory,
    ) {
        $this->data = new OnboardingData();
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(OnboardingType::class, $this->data, [
            'current_step' => $this->step,
        ]);
    }

    #[LiveAction]
    public function next(): void
    {
        // Valide le groupe de l'étape courante ; lève une 422 si invalide → on reste.
        $this->submitForm();

        if ($this->step < self::LAST_STEP) {
            ++$this->step;
            $this->resetValidation();
        }
    }

    #[LiveAction]
    public function prev(): void
    {
        if ($this->step > self::FIRST_STEP) {
            --$this->step;
        }

        // Pas de revalidation au retour : la saisie est conservée via formValues.
        $this->resetValidation();
    }

    #[LiveAction]
    public function submit(): void
    {
        // Étape 4 : revalide l'ensemble du parcours avant de conclure.
        $this->submitForm();
        $this->completed = true;
    }

    /**
     * Lance l'analyse factice de la pièce (le tap « Déposer »). Le rendu serveur
     * affiche l'état « analyse en cours » ; le contrôleur Stimulus tient le timer.
     */
    #[LiveAction]
    public function startAnalysis(): void
    {
        $this->analyzing = true;
    }

    /**
     * Valide la « pièce d'identité » à la fin de l'animation (timer Stimulus).
     * Aucun fichier n'est traité : on bascule juste le booléen porté par le Form.
     */
    #[LiveAction]
    public function provideDocument(): void
    {
        $this->analyzing = false;
        $this->formValues['documentProvided'] = '1';
    }

    #[LiveAction]
    public function fillExample(): void
    {
        $this->data = $this->catalog->exampleData();
        // Reconstruit le Form sur les données d'exemple et ré-extrait formValues.
        $this->resetForm();
        $this->resetValidation();
    }

    #[LiveAction]
    public function restart(): void
    {
        $this->step = self::FIRST_STEP;
        $this->completed = false;
        $this->analyzing = false;
        $this->data = new OnboardingData();
        $this->resetForm();
        $this->resetValidation();
    }

    /**
     * @return list<\App\Onboarding\Offer>
     */
    public function getOffers(): array
    {
        return $this->catalog->offers();
    }

    public function getSelectedOffer(): ?\App\Onboarding\Offer
    {
        $slug = \is_string($this->formValues['offer'] ?? null) ? $this->formValues['offer'] : null;

        return null !== $slug ? $this->catalog->offer($slug) : null;
    }

    private function resetValidation(): void
    {
        $this->isValidated = false;
        $this->validatedFields = [];
    }
}
