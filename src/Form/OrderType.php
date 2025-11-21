<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'    => 'Nom du devis',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('project', EntityType::class, [
                'class'        => Project::class,
                'choice_label' => 'name',
                'label'        => 'Projet associé',
                'required'     => false,
                'placeholder'  => '-- Aucun projet --',
                'attr'         => ['class' => 'form-control'],
                'help'         => 'Le projet peut être associé ultérieurement',
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => Order::STATUS_OPTIONS,
                'attr'    => ['class' => 'form-control'],
            ])
            ->add('contractType', ChoiceType::class, [
                'label'   => 'Type de contrat',
                'choices' => [
                    'Forfait (prix fixe)' => 'forfait',
                    'Régie (temps passé)' => 'regie',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('validUntil', DateType::class, [
                'label'    => 'Valable jusqu\'au',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('contingencyPercentage', NumberType::class, [
                'label'    => 'Pourcentage de contingence (%)',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'min'   => 0,
                    'max'   => 100,
                    'step'  => 0.01,
                ],
            ])
            ->add('contingenceAmount', MoneyType::class, [
                'label'    => 'Montant de contingence',
                'currency' => 'EUR',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('contingenceReason', TextareaType::class, [
                'label'    => 'Raison de la contingence',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes internes',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
