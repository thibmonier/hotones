<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contributor;
use App\Entity\Profile;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContributorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label'       => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire'),
                    new Length(max: 100),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('lastName', TextType::class, [
                'label'       => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(max: 100),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'required'    => false,
                'constraints' => [
                    new Email(message: 'Email invalide'),
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('phoneProfessional', TelType::class, [
                'label'    => 'Téléphone professionnel',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('phonePersonal', TelType::class, [
                'label'    => 'Téléphone personnel',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('birthDate', BirthdayType::class, [
                'label'    => 'Date de naissance',
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Permet de calculer l\'âge et la pyramide des âges dans le dashboard RH',
            ])
            ->add('gender', ChoiceType::class, [
                'label'       => 'Genre',
                'required'    => false,
                'placeholder' => '-- Non renseigné --',
                'choices'     => [
                    'Homme' => 'male',
                    'Femme' => 'female',
                    'Autre' => 'other',
                ],
                'attr' => ['class' => 'form-select'],
                'help' => 'Permet d\'analyser la parité homme/femme dans le dashboard RH',
            ])
            ->add('address', TextareaType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 3,
                ],
            ])
            ->add('cjm', MoneyType::class, [
                'label'    => 'CJM (Coût Journalier Moyen)',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Coût réel pour l\'entreprise',
            ])
            ->add('tjm', MoneyType::class, [
                'label'    => 'TJM (Tarif Journalier Moyen)',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Prix de vente facturé au client',
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'Actif',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'rows'  => 5,
                ],
            ])
            ->add('user', EntityType::class, [
                'label'        => 'Compte utilisateur associé',
                'class'        => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFirstName().' '.$user->getLastName().' ('.$user->getEmail().')';
                },
                'required'    => false,
                'placeholder' => '-- Aucun compte --',
                'attr'        => ['class' => 'form-select'],
            ])
            ->add('manager', EntityType::class, [
                'label'        => 'Manager responsable',
                'class'        => Contributor::class,
                'choice_label' => function (Contributor $contributor) {
                    return $contributor->getFullName();
                },
                'required'      => false,
                'placeholder'   => '-- Aucun manager --',
                'attr'          => ['class' => 'form-select'],
                'help'          => 'Sélectionnez le manager qui validera les demandes de congés de ce collaborateur',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.active = :active')
                        ->setParameter('active', true)
                        ->orderBy('c.lastName', 'ASC')
                        ->addOrderBy('c.firstName', 'ASC');
                },
            ])
            ->add('profiles', EntityType::class, [
                'label'        => 'Profils métier',
                'class'        => Profile::class,
                'choice_label' => 'name',
                'multiple'     => true,
                'expanded'     => false,
                'required'     => false,
                'attr'         => [
                    'class'            => 'form-select select2-multiple',
                    'data-placeholder' => 'Sélectionnez un ou plusieurs profils',
                ],
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.active = :active')
                        ->setParameter('active', true)
                        ->orderBy('p.name', 'ASC');
                },
            ])
            ->add('avatarFile', FileType::class, [
                'label'       => 'Avatar',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        mimeTypesMessage: 'Veuillez uploader une image valide (JPG, PNG, GIF)',
                    ),
                ],
                'attr' => ['class' => 'form-control'],
                'help' => 'Format acceptés : JPG, PNG, GIF (max 2 Mo)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contributor::class,
        ]);
    }
}
