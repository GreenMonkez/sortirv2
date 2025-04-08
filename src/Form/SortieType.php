<?php

namespace App\Form;

use App\Entity\Sortie;
use App\Repository\SiteRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function __construct(private SiteRepository $siteRepository)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
                'label_attr' => ['class' => 'required-field'],
            ])
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de la sortie',
            ])
            ->add('duration',NumberType::class, [
                'label' => 'Durée (en heures)',

            ])
            ->add('registerStartAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début d\'inscriptions',
            ])
            ->add('limitSortieAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date limite d\'inscription',
            ])
            ->add('limitMembers', NumberType::class, [
                'label' => 'Nombre de places',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
            ])
            ->add('lieu', LieuType::class, [

            ])
            ->add('site', ChoiceType::class, [
                'label' => 'Site',
                'placeholder' => 'Choisissez un site',
                'choices' => [
                    'Nantes' => $this->siteRepository->findOneBy(['name' => 'Nantes']),
                    'Rennes' => $this->siteRepository->findOneBy(['name' => 'Rennes']),
                    'Niort' => $this->siteRepository->findOneBy(['name' => 'Niort']),
                    'Quimper' => $this->siteRepository->findOneBy(['name' => 'Quimper']),
                ],


            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
