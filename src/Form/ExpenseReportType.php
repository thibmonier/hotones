<?php

namespace App\Form;

use App\Entity\ExpenseReport;
use App\Entity\Order;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ExpenseReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('expenseDate', DateType::class, [
                'label'  => 'Date du frais',
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('category', ChoiceType::class, [
                'label'   => 'Catégorie',
                'choices' => [
                    'Transport'   => ExpenseReport::CATEGORY_TRANSPORT,
                    'Repas'       => ExpenseReport::CATEGORY_MEAL,
                    'Hébergement' => ExpenseReport::CATEGORY_ACCOMMODATION,
                    'Matériel'    => ExpenseReport::CATEGORY_EQUIPMENT,
                    'Formation'   => ExpenseReport::CATEGORY_TRAINING,
                    'Autre'       => ExpenseReport::CATEGORY_OTHER,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'class' => 'form-control',
                    'rows'  => 3,
                ],
            ])
            ->add('amountHT', MoneyType::class, [
                'label'    => 'Montant HT',
                'currency' => 'EUR',
                'divisor'  => 1,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('vatRate', ChoiceType::class, [
                'label'   => 'TVA',
                'choices' => [
                    '0%'   => '0.00',
                    '5.5%' => '5.50',
                    '10%'  => '10.00',
                    '20%'  => '20.00',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('project', EntityType::class, [
                'class'        => Project::class,
                'choice_label' => fn (Project $project): string => $project->getName().($project->getClient() ? ' - '.$project->getClient()->getName() : ''),
                'required'     => false,
                'placeholder'  => '-- Sélectionner un projet --',
                'label'        => 'Projet',
                'attr'         => [
                    'class'            => 'form-select select2-search',
                    'data-placeholder' => 'Rechercher un projet...',
                ],
                'query_builder' => fn ($er) => $er->createQueryBuilder('p')
                    ->leftJoin('p.client', 'c')
                    ->orderBy('p.name', 'ASC'),
            ])
            ->add('order', EntityType::class, [
                'class'        => Order::class,
                'choice_label' => function (Order $order) {
                    $label = $order->getOrderNumber();
                    if ($order->getName()) {
                        $label .= ' - '.$order->getName();
                    }
                    if ($order->getProject()) {
                        $label .= ' ('.$order->getProject()->getName().')';
                    }

                    return $label;
                },
                'required'    => false,
                'placeholder' => '-- Sélectionner un devis --',
                'label'       => 'Devis',
                'attr'        => [
                    'class'            => 'form-select select2-search',
                    'data-placeholder' => 'Rechercher un devis...',
                ],
                'query_builder' => fn ($er) => $er->createQueryBuilder('o')
                    ->leftJoin('o.project', 'p')
                    ->orderBy('o.orderNumber', 'DESC'),
            ])
            ->add('receiptFile', FileType::class, [
                'label'       => 'Justificatif',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                        ],
                        mimeTypesMessage: 'Veuillez uploader un fichier valide (PDF, JPG, PNG)',
                    ),
                ],
                'attr' => ['class' => 'form-control'],
                'help' => 'Formats acceptés : PDF, JPG, PNG (max 5 Mo)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExpenseReport::class,
        ]);
    }
}
