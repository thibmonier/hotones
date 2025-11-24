<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Skill;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la compétence',
                'attr'  => [
                    'placeholder' => 'Ex: PHP, JavaScript, Communication...',
                    'class'       => 'form-control',
                ],
                'required' => true,
            ])
            ->add('category', ChoiceType::class, [
                'label'   => 'Catégorie',
                'choices' => [
                    'Technique'    => 'technique',
                    'Soft Skill'   => 'soft_skill',
                    'Méthodologie' => 'methodologie',
                    'Langue'       => 'langue',
                ],
                'attr'        => ['class' => 'form-select'],
                'placeholder' => 'Sélectionnez une catégorie',
                'required'    => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'rows'        => 4,
                    'class'       => 'form-control',
                    'placeholder' => 'Description détaillée de la compétence...',
                ],
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label'      => 'Compétence active',
                'required'   => false,
                'attr'       => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}
