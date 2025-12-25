<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Contributor;
use App\Entity\OrderLine;
use App\Entity\Profile;
use App\Entity\ProjectTask;
use Doctrine\ORM\EntityRepository;
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

class ProjectTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProjectTask|null $task */
        $task                = $builder->getData();
        $isLinkedToOrderLine = $task && $task->getOrderLine() !== null;

        $builder
            // Ligne budgétaire source (lecture seule)
            ->add('orderLine', EntityType::class, [
                'class'    => OrderLine::class,
                'label'    => 'Ligne budgétaire source',
                'required' => false,
                'disabled' => true, // Toujours en lecture seule
                'attr'     => [
                    'class' => 'form-select',
                ],
                'choice_label' => function (?OrderLine $line) {
                    if (!$line) {
                        return null;
                    }
                    $order = $line->getSection()->getOrder();

                    return sprintf(
                        '%s - %s (%s j)',
                        $order->getOrderNumber(),
                        $line->getDescription(),
                        $line->getDays() ?? '0',
                    );
                },
                'help' => $isLinkedToOrderLine
                    ? 'Cette tâche provient d\'une ligne budgétaire de devis (modification restreinte)'
                    : 'Pas de ligne budgétaire associée',
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la tâche',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: Développement interface utilisateur',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom de la tâche est requis'),
                    new Assert\Length(max: 255, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 4,
                    'placeholder' => 'Description détaillée de la tâche (optionnel)',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label'   => 'Type de tâche',
                'choices' => ProjectTask::getAvailableTypes(),
                'attr'    => [
                    'class' => 'form-select',
                ],
                'help' => 'Les tâches AVV et Non-vendu ne comptent pas dans la rentabilité',
            ])
            ->add('countsForProfitability', CheckboxType::class, [
                'label'    => 'Compte dans la rentabilité',
                'required' => false,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Décochez si cette tâche ne doit pas être prise en compte dans les calculs de rentabilité',
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => ProjectTask::getAvailableStatuses(),
                'attr'    => [
                    'class' => 'form-select',
                ],
            ])
            ->add('estimatedHoursSold', IntegerType::class, [
                'label'    => 'Heures vendues (estimées)',
                'required' => false,
                'disabled' => $isLinkedToOrderLine, // Verrouillé si lié à une ligne de devis
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: 40',
                    'min'         => 0,
                ],
                'help' => $isLinkedToOrderLine
                    ? 'Heures vendues (définies par la ligne budgétaire - non modifiable)'
                    : 'Nombre d\'heures vendues au client pour cette tâche',
            ])
            ->add('estimatedHoursRevised', IntegerType::class, [
                'label'    => 'Heures révisées (estimées)',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: 45',
                    'min'         => 0,
                ],
                'help' => 'Estimation révisée du nombre d\'heures nécessaires',
            ])
            ->add('progressPercentage', IntegerType::class, [
                'label' => 'Avancement (%)',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => '0',
                    'min'         => 0,
                    'max'         => 100,
                ],
                'constraints' => [
                    new Assert\Range(min: 0, max: 100, notInRangeMessage: 'L\'avancement doit être entre {{ min }}% et {{ max }}%'),
                ],
                'help' => 'Pourcentage d\'avancement de la tâche (0 à 100)',
            ])
            ->add('assignedContributor', EntityType::class, [
                'class'       => Contributor::class,
                'label'       => 'Collaborateur assigné',
                'required'    => false,
                'placeholder' => 'Sélectionner un collaborateur',
                'attr'        => [
                    'class' => 'form-select',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.active = :active')
                        ->setParameter('active', true)
                        ->orderBy('c.lastName', 'ASC')
                        ->addOrderBy('c.firstName', 'ASC');
                },
                'choice_label' => function ($contributor) {
                    return $contributor->getFirstName().' '.$contributor->getLastName();
                },
                'help' => 'Collaborateur responsable de cette tâche',
            ])
            ->add('requiredProfile', EntityType::class, [
                'class'       => Profile::class,
                'label'       => 'Profil requis',
                'required'    => false,
                'placeholder' => 'Sélectionner un profil',
                'attr'        => [
                    'class' => 'form-select',
                ],
                'choice_label' => 'name',
                'help'         => 'Type de profil nécessaire pour cette tâche',
            ])
            ->add('dailyRate', MoneyType::class, [
                'label'    => 'Tarif journalier (€)',
                'required' => false,
                'currency' => 'EUR',
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '500.00',
                ],
                'help' => 'Tarif journalier spécifique à cette tâche (optionnel)',
            ])
            ->add('startDate', DateType::class, [
                'label'    => 'Date de début',
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => [
                    'class' => 'form-control',
                ],
                'help' => 'Date prévue de début de la tâche',
            ])
            ->add('endDate', DateType::class, [
                'label'    => 'Date de fin',
                'required' => false,
                'widget'   => 'single_text',
                'attr'     => [
                    'class' => 'form-control',
                ],
                'help' => 'Date prévue de fin de la tâche',
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Position',
                'attr'  => [
                    'class' => 'form-control',
                    'min'   => 1,
                ],
                'help' => 'Ordre d\'affichage de la tâche dans la liste',
            ])
            ->add('active', CheckboxType::class, [
                'label'    => 'Tâche active',
                'required' => false,
                'attr'     => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Décochez pour désactiver cette tâche',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProjectTask::class,
        ]);
    }
}
