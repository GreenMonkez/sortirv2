<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\NotificationLog;
use App\Entity\MotifAnnulation;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($faker->sentence(3));
            $sortie->setStartAt(new \DateTimeImmutable($faker->dateTimeBetween('+2 months', '+3 months')->format('Y-m-d H:i:s')));
            $sortie->setDuration($faker->numberBetween(1, 24));
            $sortie->setRegisterStartAt(new \DateTimeImmutable($faker->dateTimeBetween('now', '+1 month')->format('Y-m-d H:i:s')));
            $sortie->setLimitSortieAt(new \DateTimeImmutable($faker->dateTimeBetween('+1 month', '+2 months')->format('Y-m-d H:i:s')));
            $sortie->setLimitMembers($faker->numberBetween(5, 50));
            $sortie->setDescription($faker->paragraph());

            // Relations
            $sortie->setSite($this->getReference('site-' . mt_rand(1, 4), Site::class));
            $sortie->setLieu($this->getReference('lieu-' . mt_rand(1, 10), Lieu::class));
            $sortie->setStatus($this->getReference('etat-' . mt_rand(1, 6), Etat::class));
            if ($sortie->getStatus()->getId() === 6) {
                $sortie->setMotifsCancel($this->getReference('motif-' . mt_rand(1, 4), MotifAnnulation::class));
            }
            $sortie->setPlanner($this->getReference('user-' . mt_rand(0, 9 ), User::class));


            $manager->persist($sortie);

            // Ajout des notifications pour les membres
            for ($j = 0; $j < mt_rand(1, 5); $j++) {
                $user = $this->getReference('user-' . mt_rand(0, 9), User::class);

                $notification = new NotificationLog();
                $notification->setUser($user);
                $notification->setSortie($sortie);
                $notification->setNotifiedAt(new \DateTimeImmutable());

                $manager->persist($notification);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            EtatFixtures::class,
            LieuFixtures::class,
            SiteFixtures::class,
            MotifAnnulationFixtures::class,
        ];
    }
}