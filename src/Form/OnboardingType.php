<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\Type\Civility;
use App\Onboarding\OfferCatalog;
use App\Onboarding\OnboardingData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire unique du parcours d'onboarding.
 *
 * Tous les champs des 4 étapes vivent dans le même Form ; le passage d'une
 * étape à l'autre ne valide que le groupe de l'étape courante grâce à la
 * closure `validation_groups` qui lit l'option `current_step` (la dernière
 * étape valide l'ensemble avant la « signature »).
 */
final class OnboardingType extends AbstractType
{
    public function __construct(
        private readonly OfferCatalog $catalog,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $offerChoices = [];
        foreach ($this->catalog->offers() as $offer) {
            $offerChoices[$offer->name] = $offer->slug;
        }

        $builder
            ->add('civility', EnumType::class, [
                'class' => Civility::class,
                'expanded' => true,
                'placeholder' => false,
                'choice_label' => static fn (Civility $civility): string => $civility->label(),
                'required' => false,
            ])
            ->add('lastName', TextType::class, ['required' => false])
            ->add('firstName', TextType::class, ['required' => false])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'html5' => true,
                'required' => false,
            ])
            ->add('nationality', CountryType::class, [
                'preferred_choices' => ['FR'],
                'placeholder' => 'Sélectionnez un pays',
                'required' => false,
            ])
            ->add('email', EmailType::class, ['required' => false])
            ->add('phone', TelType::class, ['required' => false])
            ->add('addressLine', TextType::class, ['required' => false])
            ->add('postalCode', TextType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('documentProvided', CheckboxType::class, ['required' => false])
            ->add('offer', ChoiceType::class, [
                'choices' => $offerChoices,
                'expanded' => true,
                'required' => false,
            ])
            ->add('consent', CheckboxType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OnboardingData::class,
            'current_step' => 1,
            // Démo sans persistance : la sécurité du flux est portée par le LiveComponent.
            'csrf_protection' => false,
            'validation_groups' => static function (FormInterface $form): array {
                $step = $form->getConfig()->getOption('current_step');
                if (!\is_int($step)) {
                    $step = 1;
                }

                // Dernière étape : on revalide l'ensemble avant la « signature ».
                if ($step >= 4) {
                    return ['etape1', 'etape2', 'etape3', 'etape4'];
                }

                return ['etape' . $step];
            },
        ]);

        $resolver->setAllowedTypes('current_step', 'int');
    }
}
