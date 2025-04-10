<?php

namespace App\Form;

use App\Entity\Site;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

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
                'constraints' => [
                    new NotBlank(['message' => 'Un pseudo est requis']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Votre pseudo doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre pseudo ne peut pas faire plus de {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('firstName', null, [
                'label' => 'Prénom :',
                'constraints' => [
                    new NotBlank(['message' => 'Le prénom est requis']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Votre prénom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre prénom ne peut pas faire plus de {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('lastName', null, [
                'label' => 'Nom :',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est requis']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Votre nom doit faire au moins {{ limit }} caractères',
                        'maxMessage' => 'Votre nom ne peut pas faire plus de {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('phoneNumber', null, [
                'label' => 'Téléphone :',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email :',
                'constraints' => [
                    new NotBlank(['message' => 'Un email est requis']),
                    new Email(['message' => 'L\'email {{ value }} n\'est pas valide']),
                    new Regex([
                        'pattern' => '/@campus-eni\.fr$/',
                        'message' => 'L\'email doit appartenir au domaine @campus-eni.fr.',
                    ]),
                ],
            ]);

            if (!$this->security->isGranted('ROLE_ADMIN')) {
                $builder
                    ->add('password', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'required' => false,
                    'constraints' => array(
                        new Length([
                            'min' => 6,
                            'max' => 255,
                            'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                            'maxMessage' => 'Votre mot de passe ne peut pas faire plus de {{ limit }} caractères',
                        ]),
                    ),
                    'first_options'  => array('label' => 'Mot de passe :'),
                    'second_options' => array('label' => 'Confirmation :'),
                ));
            }

            $builder
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
