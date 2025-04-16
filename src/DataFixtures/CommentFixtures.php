<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

        // Récupérer les sorties et utilisateurs existants
        $sorties = $manager->getRepository(Sortie::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        if (empty($sorties) || empty($users)) {
            throw new \Exception('Assurez-vous d\'avoir des sorties et des utilisateurs dans la base de données 
                                    avant de charger les fixtures.');
        }

        for ($i = 0; $i < 20; $i++) {
            $comment = new Comment();
            $comment->setContent($faker->sentence(10)); // Génère une phrase aléatoire
            $comment->setAuthor($faker->randomElement($users)); // Associe un utilisateur aléatoire
            $comment->setSortie($faker->randomElement($sorties)); // Associe une sortie aléatoire

            $manager->persist($comment);
        }

        $manager->flush();

    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SortieFixtures::class,
        ];
    }
}
