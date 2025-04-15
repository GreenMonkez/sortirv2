<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class LieuFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //creation d'un fixture avec faker
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $lieu = new Lieu();
            $lieu->setName($faker->sentence(3));
            $lieu->setStreet($faker->address());
            $lieu->setPostaleCode($faker->postcode());
            $lieu->setRegion($faker->city());
            $lieu->setDepartement($faker->city());
            $lieu->setCity($faker->city());
            $this->addReference('lieu-'.($i+1), $lieu);

            $manager->persist($lieu);
        }

        $manager->flush();
    }
}
