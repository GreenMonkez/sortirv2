<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'required' => false,
                'label' => 'Pseudo :',
            ])
            ->add('firstName', TextType::class, [
                'required' => false,
                'label' => 'Prénom :',
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'label' => 'Nom :',
            ])
            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email :',
            ])
            ->add('isActive', ChoiceType::class, [
                'required' => false,
                'label' => 'Actif',
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'placeholder' => 'Tous',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
