<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ContributorTechnology;
use App\Entity\Technology;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContributorTechnologyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('technology', EntityType::class, [
                'label'         => 'Technologie',
                'class'         => Technology::class,
                'choice_label'  => 'name',
                'attr'          => ['class' => 'form-select'],
                'placeholder'   => 'Sélectionnez une technologie',
                'required'      => true,
                'query_builder' => fn ($repository) => $repository
                    ->createQueryBuilder('t')
                    ->where('t.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('t.category', 'ASC')
                    ->addOrderBy('t.name', 'ASC'),
                'group_by' => fn (Technology $technology): string => ucfirst($technology->getCategory()),
            ])
            ->add('selfAssessmentLevel', ChoiceType::class, [
                'label'   => 'Niveau auto-évalué',
                'choices' => [
                    'Débutant'      => ContributorTechnology::LEVEL_BEGINNER,
                    'Intermédiaire' => ContributorTechnology::LEVEL_INTERMEDIATE,
                    'Confirmé'      => ContributorTechnology::LEVEL_CONFIRMED,
                    'Expert'        => ContributorTechnology::LEVEL_EXPERT,
                ],
                'attr'        => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un niveau',
                'required'    => true,
                'help'        => 'Votre propre évaluation de votre niveau sur cette technologie',
            ])
            ->add('managerAssessmentLevel', ChoiceType::class, [
                'label'   => 'Niveau évalué par le manager',
                'choices' => [
                    'Débutant'      => ContributorTechnology::LEVEL_BEGINNER,
                    'Intermédiaire' => ContributorTechnology::LEVEL_INTERMEDIATE,
                    'Confirmé'      => ContributorTechnology::LEVEL_CONFIRMED,
                    'Expert'        => ContributorTechnology::LEVEL_EXPERT,
                ],
                'attr'        => ['class' => 'form-select'],
                'placeholder' => 'Non évalué',
                'required'    => false,
                'help'        => 'Évaluation du manager (optionnel)',
            ])
            ->add('yearsOfExperience', NumberType::class, [
                'label'    => 'Années d\'expérience',
                'attr'     => ['class' => 'form-control', 'step' => '0.5', 'min' => '0', 'max' => '50'],
                'required' => false,
                'help'     => 'Nombre d\'années d\'expérience avec cette technologie',
                'scale'    => 1,
            ])
            ->add('firstUsedDate', DateType::class, [
                'label'    => 'Première utilisation',
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
                'required' => false,
                'help'     => 'Date de première utilisation de cette technologie',
            ])
            ->add('lastUsedDate', DateType::class, [
                'label'    => 'Dernière utilisation',
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
                'required' => false,
                'help'     => 'Date de dernière utilisation de cette technologie',
            ])
            ->add('primaryContext', ChoiceType::class, [
                'label'   => 'Contexte principal',
                'choices' => [
                    'Professionnel' => ContributorTechnology::CONTEXT_PROFESSIONAL,
                    'Personnel'     => ContributorTechnology::CONTEXT_PERSONAL,
                    'Formation'     => ContributorTechnology::CONTEXT_TRAINING,
                    'Académique'    => ContributorTechnology::CONTEXT_ACADEMIC,
                ],
                'attr'     => ['class' => 'form-select'],
                'required' => true,
                'help'     => 'Contexte principal d\'utilisation de cette technologie',
            ])
            ->add('versionUsed', TextType::class, [
                'label'    => 'Version utilisée',
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Ex: PHP 8.3, React 18, etc.'],
                'required' => false,
                'help'     => 'Version spécifique que vous maîtrisez',
            ])
            ->add('wantsToUse', CheckboxType::class, [
                'label'    => 'Souhaite continuer à utiliser cette technologie',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('wantsToImprove', CheckboxType::class, [
                'label'    => 'Souhaite monter en compétence',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
                'help'     => 'Cochez si vous souhaitez progresser sur cette technologie',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'attr'  => [
                    'rows'        => 3,
                    'class'       => 'form-control',
                    'placeholder' => 'Notes sur cette technologie (projets, certifications, remarques, etc.)',
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContributorTechnology::class,
        ]);
    }
}
