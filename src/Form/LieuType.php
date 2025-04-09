<?php

namespace App\Form;

use App\Entity\Lieu;

use App\Form\EventListener\LieuFormListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LieuType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label' => 'Nom du lieu',
            ])
            ->add('city',TextType::class, [
                'label' => 'Ville',
            ])
            ->add('postaleCode',TextType::class, [
                'label' => 'Code postal',
            ])
            ->add('street',TextType::class, [
                'label' => 'Rue',
            ])
            ->add('latitude',TextType::class, [
                'label' => 'Latitude',
                'required' => false,
            ])
            ->add('longitude',TextType::class, [
                'label' => 'Longitude',
                'required' => false,
            ])

        ;

        // Ajoutez le listener au formulaire
        $builder->addEventSubscriber(new LieuFormListener($this->entityManager));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lieu::class,
        ]);
    }
}
