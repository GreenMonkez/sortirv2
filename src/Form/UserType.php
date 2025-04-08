<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', null, [
                'label' => 'Pseudo :',
            ])
            ->add('firstName', null, [
                'label' => 'Prénom :',
            ])
            ->add('lastName', null, [
                'label' => 'Nom :',
            ])
            ->add('phoneNumber', null, [
                'label' => 'Téléphone :',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email :',
            ])
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'required' => false,
                'constraints' => array(
                    new Length(array('min' => 6)),
                ),
                'first_options'  => array('label' => 'Mot de passe :'),
                'second_options' => array('label' => 'Confirmation :'),
            ))
            ->add('photo', FileType::class, [
                'label' => 'Ma photo :',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG ou PNG).',
                    ]),
                ],
            ])
        ;

        // Add isActive field if the user is an admin
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('site', EntityType::class, [
                'label' => 'Ville de rattachement :',
                'class' => Site::class,
                'choice_label' => 'name',
            ])
                ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Compte actif',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
