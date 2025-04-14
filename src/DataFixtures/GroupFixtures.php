<?php

namespace App\DataFixtures;

use App\Entity\Group;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;


class GroupFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
       $group = new Group();
       $group->setName('Groupe 1');
       $group->setDescription('Description du groupe 1');
       $group->setOwner($this->getReference('user-3', User::class));
       $group->setSite($this->getReference('site-1', Site::class));
       $group->setCreatedAt(new \DateTimeImmutable());
       $group->addTeammate($this->getReference('user-5', User::class));
       $group->addTeammate($this->getReference('user-4', User::class));
       $group->addTeammate($this->getReference('user-6', User::class));
       $manager->persist($group);

       $group2 = new Group();
       $group2->setName('Groupe 2');
       $group2->setDescription('Description du groupe 2');
       $group2->setOwner($this->getReference('user-2', User::class));
       $group2->setSite($this->getReference('site-2', Site::class));
       $group2->setCreatedAt(new \DateTimeImmutable());
       $group2->addTeammate($this->getReference('user-1', User::class));
       $group2->addTeammate($this->getReference('user-3', User::class));
       $manager->persist($group2);

       $group3 = new Group();
       $group3->setName('Groupe 3');
       $group3->setDescription('Description du groupe 3');
       $group3->setOwner($this->getReference('user-1', User::class));
       $group3->setSite($this->getReference('site-3', Site::class));
       $group3->setCreatedAt(new \DateTimeImmutable());
       $group3->addTeammate($this->getReference('user-2', User::class));
       $group3->addTeammate($this->getReference('user-4', User::class));
       $manager->persist($group3);

        $manager->flush();


    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
