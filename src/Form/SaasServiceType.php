<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SaasProvider;
use App\Entity\SaasService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SaasServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du service',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: Google Workspace, Slack Premium, GitHub Team',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom du service est requis'),
                    new Assert\Length(max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Description du service et de ses fonctionnalités',
                ],
            ])
            ->add('provider', EntityType::class, [
                'class'         => SaasProvider::class,
                'label'         => 'Fournisseur',
                'required'      => false,
                'choice_label'  => 'name',
                'placeholder'   => '-- Souscription directe (sans fournisseur) --',
                'query_builder' => fn ($repository) => $repository->createQueryBuilder('p')
                    ->where('p.active = :active')
                    ->setParameter('active', true)
                    ->orderBy('p.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Laisser vide si le service est souscrit directement',
            ])
            ->add('category', TextType::class, [
                'label'    => 'Catégorie',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: Communication, Productivité, Développement, Design',
                    'list'        => 'categories-list',
                ],
                'help' => 'Catégorie pour organiser les services',
            ])
            ->add('serviceUrl', UrlType::class, [
                'label'    => 'URL du service',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://example.com',
                ],
            ])
            ->add('logoUrl', UrlType::class, [
                'label'    => 'URL du logo',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'https://example.com/logo.png',
                ],
            ])
            ->add('defaultMonthlyPrice', MoneyType::class, [
                'label'    => 'Prix mensuel par défaut',
                'currency' => 'EUR',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '0.00',
                ],
                'help' => 'Prix mensuel de référence (optionnel)',
            ])
            ->add('defaultYearlyPrice', MoneyType::class, [
                'label'    => 'Prix annuel par défaut',
                'currency' => 'EUR',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '0.00',
                ],
                'help' => 'Prix annuel de référence (optionnel)',
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
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Notes internes sur ce service',
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'Service actif',
                'required' => false,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Désactiver si le service n\'est plus proposé',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SaasService::class,
        ]);
    }
}
