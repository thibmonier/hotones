<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ProjectTechnology;
use App\Entity\Technology;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectTechnologyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('technology', EntityType::class, [
            'class'        => Technology::class,
            'choice_label' => 'name',
            'label'        => 'Technologie',
            'placeholder'  => '-- Sélectionner --',
            'attr'         => ['class' => 'form-control'],
        ])->add('version', TextType::class, [
            'label'    => 'Version',
            'required' => false,
            'attr'     => ['class' => 'form-control', 'placeholder' => 'ex: 8.3.0'],
        ])->add('notes', TextareaType::class, [
            'label'    => 'Notes',
            'required' => false,
            'attr'     => ['class' => 'form-control', 'rows' => 2],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectTechnology::class,
        ]);
    }
}
