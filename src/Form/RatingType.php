<?php

namespace App\Form;

use App\Entity\Rating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'attr' => ['min' => 1, 'max' => 5],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
