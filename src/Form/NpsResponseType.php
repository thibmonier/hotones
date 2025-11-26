<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class NpsResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('score', ChoiceType::class, [
                'label'       => 'Sur une échelle de 0 à 10, quelle est la probabilité que vous recommandiez nos services ?',
                'choices'     => array_combine(range(0, 10), range(0, 10)),
                'expanded'    => true,
                'multiple'    => false,
                'attr'        => ['class' => 'nps-score-buttons'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez sélectionner un score'),
                    new Range([
                        'min'               => 0,
                        'max'               => 10,
                        'notInRangeMessage' => 'Le score doit être entre {{ min }} et {{ max }}',
                    ]),
                ],
                'help' => '0 = Pas du tout probable | 10 = Extrêmement probable',
            ])
            ->add('comment', TextareaType::class, [
                'label'    => 'Pouvez-vous nous expliquer votre note ? (optionnel)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 5,
                    'placeholder' => 'Partagez votre expérience avec nous...',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
