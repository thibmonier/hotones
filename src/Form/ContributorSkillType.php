<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ContributorSkill;
use App\Entity\Skill;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContributorSkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('skill', EntityType::class, [
                'label'         => 'Compétence',
                'class'         => Skill::class,
                'choice_label'  => 'name',
                'attr'          => ['class' => 'form-select'],
                'placeholder'   => 'Sélectionnez une compétence',
                'required'      => true,
                'query_builder' => fn ($repository) => $repository->createQueryBuilder('s')
                    ->where('s.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('s.category', 'ASC')
                    ->addOrderBy('s.name', 'ASC'),
                'group_by' => fn (Skill $skill): string => $skill->getCategoryLabel(),
            ])
            ->add('selfAssessmentLevel', ChoiceType::class, [
                'label'   => 'Niveau auto-évalué',
                'choices' => [
                    'Débutant'      => ContributorSkill::LEVEL_BEGINNER,
                    'Intermédiaire' => ContributorSkill::LEVEL_INTERMEDIATE,
                    'Confirmé'      => ContributorSkill::LEVEL_CONFIRMED,
                    'Expert'        => ContributorSkill::LEVEL_EXPERT,
                ],
                'attr'        => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez un niveau',
                'required'    => true,
                'help'        => 'Votre propre évaluation de votre niveau dans cette compétence',
            ])
            ->add('managerAssessmentLevel', ChoiceType::class, [
                'label'   => 'Niveau évalué par le manager',
                'choices' => [
                    'Débutant'      => ContributorSkill::LEVEL_BEGINNER,
                    'Intermédiaire' => ContributorSkill::LEVEL_INTERMEDIATE,
                    'Confirmé'      => ContributorSkill::LEVEL_CONFIRMED,
                    'Expert'        => ContributorSkill::LEVEL_EXPERT,
                ],
                'attr'        => ['class' => 'form-select'],
                'placeholder' => 'Non évalué',
                'required'    => false,
                'help'        => 'Évaluation du manager (optionnel)',
            ])
            ->add('dateAcquired', DateType::class, [
                'label'    => 'Date d\'acquisition',
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
                'required' => false,
                'help'     => 'Date à laquelle vous avez acquis cette compétence',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'attr'  => [
                    'rows'        => 3,
                    'class'       => 'form-control',
                    'placeholder' => 'Notes sur cette compétence (projets utilisés, formations suivies, etc.)',
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContributorSkill::class,
        ]);
    }
}
