<?php

namespace App\Form;

use App\Entity\Conversation;
use App\Entity\Group;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConversationType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get the current user
        $user = $this->security->getUser();


        $builder
            ->add('name')
            ->add('participants', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'multiple' => true,
                'query_builder' => function ($repository) use ($user) {
                    return $repository->createQueryBuilder('u')
                        ->where('u != :currentUser')
                        ->setParameter('currentUser', $user);
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conversation::class,
        ]);
    }
}
