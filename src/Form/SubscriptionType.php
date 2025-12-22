<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Provider;
use App\Entity\Subscription;
use App\Entity\Vendor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'abonnement',
                'help'  => 'Ex: Disney+, Netflix Premium, Adobe Creative Cloud',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Netflix Premium',
                ],
            ])
            ->add('vendor', EntityType::class, [
                'class'        => Vendor::class,
                'choice_label' => 'name',
                'label'        => 'Fournisseur',
                'help'         => 'Le fournisseur du service (Disney, Netflix, etc.)',
                'placeholder'  => 'Sélectionnez un fournisseur',
                'attr'         => [
                    'class' => 'form-select',
                ],
            ])
            ->add('provider', EntityType::class, [
                'class'        => Provider::class,
                'choice_label' => 'name',
                'label'        => 'Canal de distribution',
                'help'         => 'Comment l\'abonnement est souscrit (Apple Store, Direct, etc.)',
                'required'     => false,
                'placeholder'  => 'Direct (par défaut)',
                'attr'         => [
                    'class' => 'form-select',
                ],
            ])
            ->add('category', TextType::class, [
                'label'    => 'Catégorie',
                'required' => false,
                'help'     => 'Ex: Streaming, Productivité, Développement',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Streaming',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 3,
                ],
            ])
            ->add('serviceUrl', UrlType::class, [
                'label'    => 'URL du service',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://',
                ],
            ])
            ->add('billingPeriod', ChoiceType::class, [
                'label'   => 'Périodicité de facturation',
                'choices' => array_flip(Subscription::BILLING_PERIODS),
                'attr'    => [
                    'class' => 'form-select',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label'    => 'Prix',
                'currency' => 'EUR',
                'help'     => 'Prix selon la périodicité sélectionnée',
                'attr'     => [
                    'class' => 'form-control',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Nombre de licences',
                'help'  => 'Nombre d\'utilisateurs ou de licences',
                'attr'  => [
                    'class' => 'form-control',
                    'min'   => 1,
                ],
            ])
            ->add('startDate', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => [
                    'class' => 'form-control',
                ],
            ])
            ->add('nextRenewalDate', DateType::class, [
                'label'    => 'Prochaine date de renouvellement',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ])
            ->add('autoRenewal', ChoiceType::class, [
                'label'   => 'Renouvellement automatique',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => array_flip(Subscription::STATUSES),
                'attr'    => [
                    'class' => 'form-select',
                ],
            ])
            ->add('externalReference', TextType::class, [
                'label'    => 'Référence externe',
                'required' => false,
                'help'     => 'Numéro de commande, ID de facture, etc.',
                'attr'     => [
                    'class' => 'form-control',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
