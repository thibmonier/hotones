<?php

namespace App\Form;

use App\Entity\SchedulerEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SchedulerEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('cronExpression', TextType::class, [
                'label' => 'Expression CRON',
                'attr'  => ['class' => 'form-control', 'placeholder' => '* * * * *'],
            ])
            ->add('command', TextType::class, [
                'label' => 'Commande Symfony',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'app:calculate-metrics'],
            ])
            ->add('timezone', ChoiceType::class, [
                'label'   => 'Fuseau horaire',
                'choices' => [
                    'Europe/Paris' => 'Europe/Paris',
                    'UTC'          => 'UTC',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('enabled', CheckboxType::class, [
                'label'    => 'Activé',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('payload', TextareaType::class, [
                'label'    => 'Payload JSON (optionnel)',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3, 'placeholder' => '{"arg":"value"}'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SchedulerEntry::class,
        ]);
    }
}
