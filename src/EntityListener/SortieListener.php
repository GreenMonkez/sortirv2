<?php

namespace App\EntityListener;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostLoadEventArgs;

class SortieListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    public function postLoad(Sortie $sortie): void
    {

        $now = new \DateTimeImmutable();
        $sortieDate = $sortie->getStartAt();
        $limitInscriptionDate = $sortie->getLimitSortieAt();
        $finished = $sortie->getStartAt()->add(new \DateInterval('PT' . $sortie->getDuration() . 'M'));
        $inscriptionStart = $sortie->getRegisterStartAt();



        if ($sortieDate < $now && $finished >= $now) {
            $etat = $this->em->getRepository(Etat::class)->findOneBy(['id' => '1']);
            $sortie->setStatus($etat);
        } elseif ($finished < $now) {
            $etat = $this->em->getRepository(Etat::class)->findOneBy(['id' => '2']);
            $sortie->setStatus($etat);
        }elseif ($limitInscriptionDate < $now && $sortieDate > $now) {
            $etat = $this->em->getRepository(Etat::class)->findOneBy(['id' => '5']);
            $sortie->setStatus($etat);
        } elseif ($limitInscriptionDate > $now && $inscriptionStart < $now) {
            $etat = $this->em->getRepository(Etat::class)->findOneBy(['id' => '4']);
            $sortie->setStatus($etat);

        }}
}