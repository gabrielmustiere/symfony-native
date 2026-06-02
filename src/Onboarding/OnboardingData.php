<?php

declare(strict_types=1);

namespace App\Onboarding;

use App\Enum\Type\Civility;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO non persisté du parcours d'onboarding.
 *
 * Les contraintes sont réparties en groupes de validation (`etape1` à `etape4`)
 * pour que seule l'étape courante soit validée au passage à la suivante.
 * Jamais écrit en base : instancié et détruit dans le cycle du LiveComponent.
 */
final class OnboardingData
{
    // Étape ① — Identité
    #[Assert\NotNull(message: 'Sélectionnez une civilité.', groups: ['etape1'])]
    public ?Civility $civility = null;

    #[Assert\NotBlank(message: 'Indiquez votre nom.', groups: ['etape1'])]
    #[Assert\Length(max: 80, groups: ['etape1'])]
    public ?string $lastName = null;

    #[Assert\NotBlank(message: 'Indiquez votre prénom.', groups: ['etape1'])]
    #[Assert\Length(max: 80, groups: ['etape1'])]
    public ?string $firstName = null;

    #[Assert\NotNull(message: 'Indiquez votre date de naissance.', groups: ['etape1'])]
    #[Assert\LessThanOrEqual(value: '-18 years', message: 'Vous devez être majeur (18 ans ou plus) pour ouvrir un compte.', groups: ['etape1'])]
    public ?\DateTimeImmutable $birthDate = null;

    #[Assert\NotBlank(message: 'Sélectionnez votre nationalité.', groups: ['etape1'])]
    public ?string $nationality = null;

    // Étape ② — Coordonnées
    #[Assert\NotBlank(message: 'Indiquez votre adresse e-mail.', groups: ['etape2'])]
    #[Assert\Email(message: "Cette adresse e-mail n'est pas valide.", groups: ['etape2'])]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Indiquez votre numéro de téléphone.', groups: ['etape2'])]
    #[Assert\Regex(pattern: '/^(?:\+33|0)\s?[1-9](?:[\s.\-]?\d{2}){4}$/', message: 'Numéro de téléphone français invalide.', groups: ['etape2'])]
    public ?string $phone = null;

    #[Assert\NotBlank(message: 'Indiquez votre adresse.', groups: ['etape2'])]
    public ?string $addressLine = null;

    #[Assert\NotBlank(message: 'Indiquez votre code postal.', groups: ['etape2'])]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit comporter 5 chiffres.', groups: ['etape2'])]
    public ?string $postalCode = null;

    #[Assert\NotBlank(message: 'Indiquez votre ville.', groups: ['etape2'])]
    public ?string $city = null;

    // Étape ③ — Pièce d'identité (dépôt simulé)
    #[Assert\IsTrue(message: "Déposez votre pièce d'identité pour continuer.", groups: ['etape3'])]
    public bool $documentProvided = false;

    // Étape ④ — Offre + signature
    #[Assert\NotBlank(message: 'Choisissez une offre.', groups: ['etape4'])]
    public ?string $offer = null;

    #[Assert\IsTrue(message: 'Vous devez accepter les conditions pour finaliser.', groups: ['etape4'])]
    public bool $consent = false;
}
