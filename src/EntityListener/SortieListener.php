<?php

namespace App\EntityListener;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;



class SortieListener
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    /**
     * Méthode permettant de gérer le statut d'une sortie à chaque fois qu'elle est chargée
     * Class SortieListener
     * @package App\EntityListener
     * @ORM\EntityListeners({"App\EventListener\SortieListener"})
     */
    public function postLoad(Sortie $sortie): void
    {

        $now = new \DateTimeImmutable();
        $sortieDate = $sortie->getStartAt();
        $limitInscriptionDate = $sortie->getLimitSortieAt();
        $finished = $sortie->getStartAt()->add(new \DateInterval('PT' . $sortie->getDuration() . 'M'));
        $inscriptionStart = $sortie->getRegisterStartAt();


        if ($sortieDate < $now && $finished >= $now) {
            $etat = $this->em->getRepository(Etat::class)->find(1);// En cours
            $sortie->setStatus($etat);
        } elseif ($finished < $now) {
            $etat = $this->em->getRepository(Etat::class)->find(5);// Terminée
            $sortie->setStatus($etat);
        }elseif ($limitInscriptionDate < $now && $sortieDate > $now) {
            $etat = $this->em->getRepository(Etat::class)->find(4);// Cloturée)
            $sortie->setStatus($etat);
        } elseif ($limitInscriptionDate > $now && $inscriptionStart < $now) {
            $etat = $this->em->getRepository(Etat::class)->find(3);// Ouverte
            $sortie->setStatus($etat);

        }}


}