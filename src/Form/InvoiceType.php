<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class'        => Client::class,
                'choice_label' => 'name',
                'label'        => 'Client',
                'required'     => true,
                'placeholder'  => '-- Sélectionnez un client --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('project', EntityType::class, [
                'class'        => Project::class,
                'choice_label' => 'name',
                'label'        => 'Projet',
                'required'     => false,
                'placeholder'  => '-- Aucun projet --',
                'attr'         => ['class' => 'form-control'],
                'help'         => 'Optionnel : lier la facture à un projet',
            ])
            ->add('issuedAt', DateType::class, [
                'label'    => 'Date d\'émission',
                'widget'   => 'single_text',
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('dueDate', DateType::class, [
                'label'    => 'Date d\'échéance',
                'widget'   => 'single_text',
                'required' => true,
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Date limite de paiement',
            ])
            ->add('amountHt', MoneyType::class, [
                'label'    => 'Montant HT',
                'currency' => 'EUR',
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('tvaRate', ChoiceType::class, [
                'label'   => 'Taux de TVA (%)',
                'choices' => [
                    '20%'          => '20.00',
                    '10%'          => '10.00',
                    '5.5%'         => '5.50',
                    '2.1%'         => '2.10',
                    '0% (exonéré)' => '0.00',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => Invoice::STATUS_OPTIONS,
                'attr'    => ['class' => 'form-control'],
            ])
            ->add('paymentTerms', TextareaType::class, [
                'label'    => 'Conditions de paiement',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => 'Ex: Paiement à 30 jours fin de mois',
                ],
            ])
            ->add('internalNotes', TextareaType::class, [
                'label'    => 'Notes internes',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Notes privées, non affichées sur la facture',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invoice::class,
        ]);
    }
}
