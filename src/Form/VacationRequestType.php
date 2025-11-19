<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Vacation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VacationRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label'   => 'Type de congé',
                'choices' => Vacation::getAvailableTypes(),
                'attr'    => [
                    'class' => 'form-select',
                ],
            ])
            ->add('startDate', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de début est obligatoire'),
                    new Assert\GreaterThanOrEqual(
                        value: 'today',
                        message: 'La date de début doit être dans le futur',
                    ),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'attr'   => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de fin est obligatoire'),
                ],
            ])
            ->add('dailyHours', NumberType::class, [
                'label' => 'Heures par jour',
                'attr'  => [
                    'class' => 'form-control',
                    'min'   => '0',
                    'max'   => '8',
                    'step'  => '0.5',
                ],
                'html5' => true,
                'help'  => 'Nombre d\'heures d\'absence par jour (8h = journée complète)',
            ])
            ->add('reason', TextareaType::class, [
                'label'    => 'Motif',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => 'Précisez le motif de votre demande (optionnel)',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vacation::class,
        ]);
    }
}
