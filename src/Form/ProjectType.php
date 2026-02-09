<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\ServiceCategory;
use App\Entity\Technology;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'    => 'Nom du projet',
                'required' => true,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('client', EntityType::class, [
                'class'        => Client::class,
                'choice_label' => 'name',
                'label'        => 'Client',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un client --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('isInternal', CheckboxType::class, [
                'label'    => 'Projet interne',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Actif'   => 'active',
                    'Terminé' => 'completed',
                    'Annulé'  => 'cancelled',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('projectType', ChoiceType::class, [
                'label'   => 'Type de projet',
                'choices' => [
                    'Forfait' => 'forfait',
                    'Régie'   => 'regie',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('startDate', DateType::class, [
                'label'    => 'Date de début',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('endDate', DateType::class, [
                'label'    => 'Date de fin',
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('purchasesAmount', MoneyType::class, [
                'label'    => 'Montant des achats',
                'currency' => 'EUR',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('purchasesDescription', TextareaType::class, [
                'label'    => 'Description des achats',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            // Rôles projet avec Users
            ->add('keyAccountManager', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn (User $user): string => $user->getFirstName().' '.$user->getLastName(),
                'label'        => 'KAM (Key Account Manager)',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un utilisateur --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('projectManager', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn (User $user): string => $user->getFirstName().' '.$user->getLastName(),
                'label'        => 'Chef de projet',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un utilisateur --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('projectDirector', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn (User $user): string => $user->getFirstName().' '.$user->getLastName(),
                'label'        => 'Directeur de projet',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un utilisateur --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('salesPerson', EntityType::class, [
                'class'        => User::class,
                'choice_label' => fn (User $user): string => $user->getFirstName().' '.$user->getLastName(),
                'label'        => 'Commercial',
                'required'     => false,
                'placeholder'  => '-- Sélectionner un utilisateur --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('serviceCategory', EntityType::class, [
                'class'        => ServiceCategory::class,
                'choice_label' => 'name',
                'label'        => 'Catégorie de service',
                'required'     => false,
                'placeholder'  => '-- Sélectionner une catégorie --',
                'attr'         => ['class' => 'form-control'],
            ])
            ->add('technologies', EntityType::class, [
                'class'        => Technology::class,
                'choice_label' => 'name',
                'label'        => 'Technologies',
                'multiple'     => true,
                'expanded'     => false,
                'required'     => false,
                'attr'         => [
                    'class'            => 'form-control select2-multiple',
                    'data-placeholder' => 'Sélectionner les technologies',
                ],
            ])
            ->add('repoLinks', TextareaType::class, [
                'label'    => 'Liens dépôts (un par ligne)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => "https://gitlab.com/..\nhttps://github.com/..",
                ],
            ])
            ->add('envLinks', TextareaType::class, [
                'label'    => 'Liens environnements (un par ligne)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => "https://staging.example.com\nhttps://prod.example.com",
                ],
            ])
            ->add('dbAccess', TextareaType::class, [
                'label'    => 'Accès BDD',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('sshAccess', TextareaType::class, [
                'label'    => 'Accès SSH',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('ftpAccess', TextareaType::class, [
                'label'    => 'Accès FTP',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
