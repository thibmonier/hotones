<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ContributorSatisfaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ContributorSatisfactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $scoreChoices = [
            '⭐ 1 - Très insatisfait'       => 1,
            '⭐⭐ 2 - Insatisfait'          => 2,
            '⭐⭐⭐ 3 - Neutre'             => 3,
            '⭐⭐⭐⭐ 4 - Satisfait'        => 4,
            '⭐⭐⭐⭐⭐ 5 - Très satisfait' => 5,
        ];

        $builder
            ->add('overallScore', ChoiceType::class, [
                'label'       => 'Satisfaction globale',
                'choices'     => $scoreChoices,
                'expanded'    => false,
                'placeholder' => '-- Sélectionnez votre niveau de satisfaction --',
                'attr'        => ['class' => 'form-select'],
                'constraints' => [
                    new NotBlank(message: 'La satisfaction globale est obligatoire'),
                    new Range(
                        min: 1,
                        max: 5,
                    ),
                ],
                'help' => 'Comment évaluez-vous votre satisfaction générale ce mois-ci ?',
            ])
            ->add('projectsScore', ChoiceType::class, [
                'label'       => 'Projets / Missions',
                'choices'     => $scoreChoices,
                'expanded'    => false,
                'required'    => false,
                'placeholder' => '-- Optionnel --',
                'attr'        => ['class' => 'form-select'],
                'help'        => 'Comment évaluez-vous les projets sur lesquels vous avez travaillé ?',
            ])
            ->add('teamScore', ChoiceType::class, [
                'label'       => 'Équipe / Management',
                'choices'     => $scoreChoices,
                'expanded'    => false,
                'required'    => false,
                'placeholder' => '-- Optionnel --',
                'attr'        => ['class' => 'form-select'],
                'help'        => 'Comment évaluez-vous votre relation avec l\'équipe et le management ?',
            ])
            ->add('workEnvironmentScore', ChoiceType::class, [
                'label'       => 'Environnement de travail',
                'choices'     => $scoreChoices,
                'expanded'    => false,
                'required'    => false,
                'placeholder' => '-- Optionnel --',
                'attr'        => ['class' => 'form-select'],
                'help'        => 'Comment évaluez-vous vos conditions de travail (outils, bureaux, etc.) ?',
            ])
            ->add('workLifeBalanceScore', ChoiceType::class, [
                'label'       => 'Équilibre vie pro / perso',
                'choices'     => $scoreChoices,
                'expanded'    => false,
                'required'    => false,
                'placeholder' => '-- Optionnel --',
                'attr'        => ['class' => 'form-select'],
                'help'        => 'Comment évaluez-vous l\'équilibre entre votre vie professionnelle et personnelle ?',
            ])
            ->add('positivePoints', TextareaType::class, [
                'label'    => 'Points positifs',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Qu\'est-ce qui vous a particulièrement plu ce mois-ci ?',
                ],
                'help' => 'Partagez ce qui vous a rendu heureux ou motivé',
            ])
            ->add('improvementPoints', TextareaType::class, [
                'label'    => 'Points d\'amélioration',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Quels aspects pourraient être améliorés selon vous ?',
                ],
                'help' => 'Vos suggestions pour améliorer votre expérience',
            ])
            ->add('comment', TextareaType::class, [
                'label'    => 'Commentaire libre',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Souhaitez-vous ajouter quelque chose ?',
                ],
                'help' => 'Espace libre pour tout commentaire supplémentaire',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContributorSatisfaction::class,
        ]);
    }
}
