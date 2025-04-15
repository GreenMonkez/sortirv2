<?php

namespace App\DataFixtures;

use App\Entity\Conversation;
use App\Entity\Group;
use App\Entity\Message;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;


class GroupFixtures extends Fixture implements DependentFixtureInterface
{

    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
       $group1 = new Group();
       $group1->setName('Groupe 1');
       $group1->setDescription('Description du groupe 1');
       $group1->setOwner($this->getReference('user-3', User::class));
       $group1->setSite($this->getReference('site-1', Site::class));
       $group1->addTeammate($this->getReference('user-5', User::class));
       $group1->addTeammate($this->getReference('user-4', User::class));
       $group1->addTeammate($this->getReference('user-6', User::class));
       $this->addReference('group-1', $group1);



       // CONVERSATION

        $conversation1 = new Conversation();
        $conversation1->setName($this->faker->sentence(3));
        $conversation1->setPrivateGroup($this->getReference('group-1', Group::class));
        $conversation1->getPrivateGroup()->setConversation($conversation1);
        $conversation1->addParticipant($conversation1->getPrivateGroup()->getOwner());
        foreach ($conversation1->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation1->addParticipant($teammate);
        }
        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message1 = new Message();
            $message1->setContent($this->faker->text(200));
            $message1->setSender($group1->getTeammate()->get(mt_rand(0, 2)));
            $message1->setConversation($conversation1);
            $conversation1->addParticipant($message1->getSender());

            $manager->persist($message1);
        }

        $group1->setConversation($conversation1);
        $manager->persist($group1);


       $group2 = new Group();
       $group2->setName('Groupe 2');
       $group2->setDescription('Description du groupe 2');
       $group2->setOwner($this->getReference('user-2', User::class));
       $group2->setSite($this->getReference('site-2', Site::class));
       $group2->addTeammate($this->getReference('user-1', User::class));
       $group2->addTeammate($this->getReference('user-3', User::class));
       $this->addReference('group-2', $group2);

        // CONVERSATION

        $conversation2 = new Conversation();
        $conversation2->setName($this->faker->sentence(3));
        $conversation2->setPrivateGroup($this->getReference('group-2', Group::class));
        $conversation2->getPrivateGroup()->setConversation($conversation2);
        $conversation2->addParticipant($conversation2->getPrivateGroup()->getOwner());
        foreach ($conversation2->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation2->addParticipant($teammate);
        }

        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message2 = new Message();
            $message2->setContent($this->faker->text(200));
            $message2->setSender($group2->getTeammate()->get(mt_rand(0, 1)));
            $message2->setConversation($conversation2);
            $conversation2->addParticipant($message2->getSender());

            $manager->persist($message2);
        }

        $group2->setConversation($conversation2);
        $manager->persist($group2);


       $group3 = new Group();
       $group3->setName('Groupe 3');
       $group3->setDescription('Description du groupe 3');
       $group3->setOwner($this->getReference('user-1', User::class));
       $group3->setSite($this->getReference('site-3', Site::class));
       $group3->addTeammate($this->getReference('user-2', User::class));
       $group3->addTeammate($this->getReference('user-4', User::class));
       $this->addReference('group-3', $group3);


        // CONVERSATION

        $conversation3 = new Conversation();
        $conversation3->setName($this->faker->sentence(3));
        $conversation3->setPrivateGroup($this->getReference('group-3', Group::class));
        $conversation3->getPrivateGroup()->setConversation($conversation3);
        $conversation3->addParticipant($conversation3->getPrivateGroup()->getOwner());
        foreach ($conversation3->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation3->addParticipant($teammate);
        }

        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message3 = new Message();
            $message3->setContent($this->faker->text(200));
            $message3->setSender($group3->getTeammate()->get(mt_rand(0, 1)));
            $message3->setConversation($conversation3);
            $conversation3->addParticipant($message3->getSender());

            $manager->persist($message3);
        }

        $group3->setConversation($conversation3);
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
