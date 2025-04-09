<?php

namespace App\EntityListener;

use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;

class SortieArchiver
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Méthode pour archiver les sorties
     * @return void
     */
    public function archiverSorties(): void
    {
        $now = new \DateTimeImmutable();

        // Récupérer toutes les sorties non archivées
        $sorties = $this->em->getRepository(Sortie::class)->findBy(['isArchive' => false]);

        foreach ($sorties as $sortie) {
            $finished = $sortie->getStartAt()->add(new \DateInterval('PT' . $sortie->getDuration() . 'M'));
            $archive = $finished->add(new \DateInterval('P1M'));

            if ($archive < $now) {
                $sortie->setIsArchive(true);
                $this->em->persist($sortie);
            }
        }

        $this->em->flush(); // Enregistrer toutes les modifications en base
    }
}