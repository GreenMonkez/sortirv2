<?php

namespace App\DataFixtures;

use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SiteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create sites : Nantes, Rennes, Niort and Quimper
        $site1 = new Site();
        $site1->setName('Nantes');
        $this->addReference('site-1', $site1);
        $manager->persist($site1);

        $site2 = new Site();
        $site2->setName('Rennes');
        $this->addReference('site-2', $site2);
        $manager->persist($site2);

        $site3 = new Site();
        $site3->setName('Niort');
        $this->addReference('site-3', $site3);
        $manager->persist($site3);

        $site4 = new Site();
        $site4->setName('Quimper');
        $this->addReference('site-4', $site4);
        $manager->persist($site4);

        $manager->flush();
    }
}
