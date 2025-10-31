<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contributor;
use App\Entity\ProjectSubTask;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProjectSubTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'       => 'Titre',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
                'attr'        => ['class' => 'form-control'],
            ])
            ->add('assignee', EntityType::class, [
                'label'        => 'Assigné à',
                'class'        => Contributor::class,
                'required'     => false,
                'placeholder'  => 'Sélectionner',
                'attr'         => ['class' => 'form-select'],
                'choice_label' => 'name',
            ])
            ->add('initialEstimatedHours', NumberType::class, [
                'label'       => 'Estimation initiale (heures)',
                'scale'       => 2,
                'html5'       => true,
                'attr'        => ['class' => 'form-control', 'min' => 0, 'step' => '0.25'],
                'constraints' => [new Assert\GreaterThanOrEqual(0)],
            ])
            ->add('remainingHours', NumberType::class, [
                'label'       => 'RAF (heures)',
                'scale'       => 2,
                'html5'       => true,
                'attr'        => ['class' => 'form-control', 'min' => 0, 'step' => '0.25'],
                'constraints' => [new Assert\GreaterThanOrEqual(0)],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => ProjectSubTask::getAvailableStatuses(),
                'attr'    => ['class' => 'form-select'],
            ])
            ->add('position', IntegerType::class, [
                'label'       => 'Position',
                'attr'        => ['class' => 'form-control', 'min' => 1],
                'constraints' => [new Assert\GreaterThanOrEqual(1)],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectSubTask::class,
        ]);
    }
}
