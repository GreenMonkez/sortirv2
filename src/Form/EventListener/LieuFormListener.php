<?php

namespace App\Form\EventListener;

use App\Entity\Lieu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class LieuFormListener implements EventSubscriberInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        // Vérifiez si les champs nécessaires pour l'adresse sont présents
        if (isset($data['name'], $data['city'], $data['postaleCode'], $data['street'])) {
            $existingLieu = $this->entityManager->getRepository(Lieu::class)->findOneBy([
                'name' => $data['name'],
                'city' => $data['city'],
                'postaleCode' => $data['postaleCode'],
                'street' => $data['street'],
            ]);

            if ($existingLieu) {
                // Remplacez les données du formulaire par l'entité existante
                $event->setData([
                    'name' => $existingLieu->getName(),
                    'city' => $existingLieu->getCity(),
                    'postaleCode' => $existingLieu->getPostaleCode(),
                    'street' => $existingLieu->getStreet(),
                    'latitude' => $existingLieu->getLatitude(),
                    'longitude' => $existingLieu->getLongitude(),
                ]);

                // Remplacez l'entité dans le formulaire
                $form->setData($existingLieu);
            }
        }
    }
}