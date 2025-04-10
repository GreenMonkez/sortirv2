<?php

namespace App\DataFixtures;

use App\Entity\MotifAnnulation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MotifAnnulationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $motif1 = new MotifAnnulation();
        $motif1->setName('Mauvaise Météo');
        $motif1->setCommentaire('La météo n\'est pas favorable pour la sortie.');
        $this->addReference('motif-1', $motif1);
        $manager->persist($motif1);

        $motif2 = new MotifAnnulation();
        $motif2->setName('Problème de Logistique');
        $motif2->setCommentaire('Un imprévu logistique a empêché la sortie.');
        $this->addReference('motif-2', $motif2);
        $manager->persist($motif2);

        $motif3 = new MotifAnnulation();
        $motif3->setName('Problème familiale');
        $motif3->setCommentaire('Un problème familial a empêché la sortie.');
        $this->addReference('motif-3', $motif3);
        $manager->persist($motif3);

        $motif4 = new MotifAnnulation();
        $motif4->setName('Pas assez de participants');
        $motif4->setCommentaire('Le nombre de participants est insuffisant pour maintenir la sortie.');
        $this->addReference('motif-4', $motif4);
        $manager->persist($motif4);

        $manager->flush();
    }
}
