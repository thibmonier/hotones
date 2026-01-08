<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\NpsSurvey;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class NpsSurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'label'         => 'Projet',
                'class'         => Project::class,
                'choice_label'  => 'name',
                'placeholder'   => '-- Sélectionnez un projet --',
                'attr'          => ['class' => 'form-select'],
                'query_builder' => fn ($er) => $er->createQueryBuilder('p')
                    ->orderBy('p.name', 'ASC'),
                'constraints' => [
                    new NotBlank(message: 'Le projet est obligatoire'),
                ],
            ])
            ->add('recipientEmail', EmailType::class, [
                'label'       => 'Email du destinataire',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire'),
                    new Email(message: 'Email invalide'),
                ],
                'help' => 'Email du contact client qui recevra l\'enquête de satisfaction',
            ])
            ->add('recipientName', TextType::class, [
                'label'    => 'Nom du destinataire',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'help'     => 'Optionnel - permet de personnaliser l\'email',
            ])
            ->add('expiresAt', DateType::class, [
                'label'  => 'Date d\'expiration',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
                'help'   => 'Après cette date, l\'enquête ne sera plus valide (par défaut : 30 jours)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NpsSurvey::class,
        ]);
    }
}
