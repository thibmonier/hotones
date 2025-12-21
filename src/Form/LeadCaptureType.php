<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\LeadCapture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LeadCaptureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Jean',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères',
                    ),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Dupont',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email professionnel',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'jean.dupont@agence.fr',
                ],
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire'),
                    new Email(message: 'L\'email "{{ value }}" n\'est pas valide'),
                ],
            ])
            ->add('company', TextType::class, [
                'label'    => 'Nom de votre agence',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Mon Agence Web',
                ],
            ])
            ->add('phone', TelType::class, [
                'label'    => 'Téléphone (optionnel)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '+33 6 12 34 56 78',
                ],
            ])
            ->add('marketingConsent', CheckboxType::class, [
                'label'    => 'J\'accepte de recevoir des emails avec des conseils et actualités sur la gestion d\'agence',
                'required' => false,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('rgpdConsent', CheckboxType::class, [
                'label'  => 'J\'accepte la politique de confidentialité et le traitement de mes données personnelles',
                'mapped' => false,
                'attr'   => [
                    'class' => 'form-check-input',
                ],
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter la politique de confidentialité'),
                ],
            ])
            ->add('source', HiddenType::class, [
                'data' => $options['source'] ?? LeadCapture::SOURCE_OTHER,
            ])
            ->add('contentType', HiddenType::class, [
                'data' => $options['content_type'] ?? 'guide-kpis',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'   => LeadCapture::class,
            'source'       => LeadCapture::SOURCE_OTHER,
            'content_type' => 'guide-kpis',
        ]);
    }
}
