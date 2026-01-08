<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SaasService;
use App\Entity\SaasSubscription;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SaasSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('service', EntityType::class, [
                'class'        => SaasService::class,
                'label'        => 'Service',
                'required'     => true,
                'choice_label' => function (?SaasService $service) {
                    if (!$service) {
                        return null;
                    }
                    $provider = $service->getProvider();

                    return $provider
                        ? sprintf('%s (%s)', $service->getName(), $provider->getName())
                        : $service->getName();
                },
                'query_builder' => fn ($repository) => $repository->createQueryBuilder('s')
                    ->leftJoin('s.provider', 'p')
                    ->where('s.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('p.name', 'ASC')
                    ->addOrderBy('s.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le service est requis'),
                ],
            ])
            ->add('customName', TextType::class, [
                'label'    => 'Nom personnalisé',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Nom personnalisé pour cet abonnement (optionnel)',
                ],
                'help' => 'Si vide, le nom du service sera utilisé',
            ])
            ->add('billingPeriod', ChoiceType::class, [
                'label'   => 'Périodicité de facturation',
                'choices' => array_flip(SaasSubscription::BILLING_PERIODS),
                'attr'    => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La périodicité est requise'),
                ],
            ])
            ->add('price', MoneyType::class, [
                'label'    => 'Prix',
                'currency' => 'EUR',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '0.00',
                ],
                'help'        => 'Prix par mois ou par an selon la périodicité',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le prix est requis'),
                    new Assert\PositiveOrZero(message: 'Le prix doit être positif'),
                ],
            ])
            ->add('currency', ChoiceType::class, [
                'label'   => 'Devise',
                'choices' => [
                    'Euro (EUR)'           => 'EUR',
                    'Dollar (USD)'         => 'USD',
                    'Livre Sterling (GBP)' => 'GBP',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité / Licences',
                'attr'  => [
                    'class' => 'form-control',
                    'min'   => 1,
                ],
                'help'        => 'Nombre de licences ou d\'utilisateurs',
                'constraints' => [
                    new Assert\NotBlank(message: 'La quantité est requise'),
                    new Assert\Positive(message: 'La quantité doit être positive'),
                ],
            ])
            ->add('startDate', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'attr'   => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de début est requise'),
                ],
            ])
            ->add('endDate', DateType::class, [
                'label'    => 'Date de fin',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                ],
                'help' => 'Laisser vide si l\'abonnement est actif',
            ])
            ->add('nextRenewalDate', DateType::class, [
                'label'  => 'Prochaine date de renouvellement',
                'widget' => 'single_text',
                'attr'   => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date de renouvellement est requise'),
                ],
            ])
            ->add('lastRenewalDate', DateType::class, [
                'label'    => 'Dernière date de renouvellement',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ])
            ->add('autoRenewal', CheckboxType::class, [
                'label'    => 'Renouvellement automatique',
                'required' => false,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
                'help' => 'L\'abonnement sera renouvelé automatiquement à la date de renouvellement',
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => array_flip(SaasSubscription::STATUSES),
                'attr'    => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le statut est requis'),
                ],
            ])
            ->add('externalReference', TextType::class, [
                'label'    => 'Référence externe',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Numéro de commande, référence fournisseur, etc.',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Notes internes sur cet abonnement',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaasSubscription::class,
        ]);
    }
}
