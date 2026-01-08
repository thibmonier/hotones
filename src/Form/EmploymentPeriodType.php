<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Profile;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class EmploymentPeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contributor', EntityType::class, [
                'label'        => 'Collaborateur',
                'class'        => Contributor::class,
                'choice_label' => 'name',
                'required'     => true,
                'placeholder'  => '-- Sélectionner un collaborateur --',
                'attr'         => ['class' => 'form-select'],
                'constraints'  => [
                    new NotBlank(message: 'Le collaborateur est obligatoire'),
                ],
            ])
            ->add('startDate', DateType::class, [
                'label'       => 'Date de début',
                'widget'      => 'single_text',
                'required'    => true,
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'La date de début est obligatoire'),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label'    => 'Date de fin',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Laisser vide si la période est en cours',
            ])
            ->add('salary', MoneyType::class, [
                'label'    => 'Salaire mensuel brut',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => [
                    'class'              => 'form-control',
                    'data-calculate-cjm' => 'true',
                ],
                'help' => 'Salaire brut mensuel du collaborateur',
            ])
            ->add('cjm', MoneyType::class, [
                'label'    => 'CJM (Coût Journalier Moyen)',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => [
                    'class'           => 'form-control',
                    'data-cjm-target' => 'true',
                ],
                'help' => 'Calculé automatiquement si non fourni',
            ])
            ->add('tjm', MoneyType::class, [
                'label'    => 'TJM (Tarif Journalier Moyen)',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Tarif de vente moyen',
            ])
            ->add('weeklyHours', NumberType::class, [
                'label'    => 'Heures hebdomadaires',
                'required' => true,
                'scale'    => 2,
                'attr'     => [
                    'class'              => 'form-control',
                    'step'               => '0.5',
                    'data-calculate-cjm' => 'true',
                ],
                'data'        => 35.0,
                'constraints' => [
                    new Range(
                        min: 1,
                        max: 48,
                        notInRangeMessage: 'Les heures hebdomadaires doivent être entre {{ min }} et {{ max }}',
                    ),
                ],
                'help' => 'Nombre d\'heures travaillées par semaine (ex: 35, 39)',
            ])
            ->add('workTimePercentage', NumberType::class, [
                'label'    => 'Temps de travail (%)',
                'required' => true,
                'scale'    => 2,
                'attr'     => [
                    'class'              => 'form-control',
                    'step'               => '5',
                    'data-calculate-cjm' => 'true',
                ],
                'data'        => 100.0,
                'constraints' => [
                    new Range(
                        min: 1,
                        max: 100,
                        notInRangeMessage: 'Le pourcentage doit être entre {{ min }} et {{ max }}',
                    ),
                ],
                'help' => '100% = temps plein, 80% = 4/5ème, etc.',
            ])
            ->add('profiles', EntityType::class, [
                'label'        => 'Profils métier pendant cette période',
                'class'        => Profile::class,
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false,
                'attr'         => ['class' => 'form-check-input'],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 3,
                ],
            ])
        ;

        // Validation personnalisée : endDate doit être après startDate
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $period = $event->getData();

            if ($period->endDate && $period->endDate < $period->startDate) {
                $event->getForm()->get('endDate')->addError(
                    new \Symfony\Component\Form\FormError('La date de fin doit être postérieure à la date de début'),
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmploymentPeriod::class,
        ]);
    }
}
