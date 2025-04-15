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
       $group = new Group();
       $group->setName('Groupe 1');
       $group->setDescription('Description du groupe 1');
       $group->setOwner($this->getReference('user-3', User::class));
       $group->setSite($this->getReference('site-1', Site::class));
       $group->setCreatedAt(new \DateTimeImmutable());
       $group->addTeammate($this->getReference('user-5', User::class));
       $group->addTeammate($this->getReference('user-4', User::class));
       $group->addTeammate($this->getReference('user-6', User::class));
       $this->addReference('group-1', $group);



       // CONVERSATION

        $conversation = new Conversation();
        $conversation->setName($this->faker->sentence(3));
        $conversation->setPrivateGroup($this->getReference('group-1', Group::class));
        $conversation->getPrivateGroup()->setConversation($conversation);
        $conversation->addParticipant($conversation->getPrivateGroup()->getOwner());
        foreach ($conversation->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation->addParticipant($teammate);
        }
        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message = new Message();
            $message->setContent($this->faker->text(200));
            $message->setSender($group->getTeammate()->get(mt_rand(0, 2)));
            $message->setConversation($conversation);
            $conversation->addParticipant($message->getSender());

            $manager->persist($message);
        }

        $group->setConversation($conversation);
        $manager->persist($group);


       $group2 = new Group();
       $group2->setName('Groupe 2');
       $group2->setDescription('Description du groupe 2');
       $group2->setOwner($this->getReference('user-2', User::class));
       $group2->setSite($this->getReference('site-2', Site::class));
       $group2->setCreatedAt(new \DateTimeImmutable());
       $group2->addTeammate($this->getReference('user-1', User::class));
       $group2->addTeammate($this->getReference('user-3', User::class));
       $this->addReference('group-2', $group2);

        // CONVERSATION

        $conversation = new Conversation();
        $conversation->setName($this->faker->sentence(3));
        $conversation->setPrivateGroup($this->getReference('group-2', Group::class));
        $conversation->getPrivateGroup()->setConversation($conversation);
        $conversation->addParticipant($conversation->getPrivateGroup()->getOwner());
        foreach ($conversation->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation->addParticipant($teammate);
        }

        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message = new Message();
            $message->setContent($this->faker->text(200));
            $message->setSender($group->getTeammate()->get(mt_rand(0, 2)));
            $message->setConversation($conversation);
            $conversation->addParticipant($message->getSender());

            $manager->persist($message);
        }

        $group->setConversation($conversation);
        $manager->persist($group);


       $group3 = new Group();
       $group3->setName('Groupe 3');
       $group3->setDescription('Description du groupe 3');
       $group3->setOwner($this->getReference('user-1', User::class));
       $group3->setSite($this->getReference('site-3', Site::class));
       $group3->setCreatedAt(new \DateTimeImmutable());
       $group3->addTeammate($this->getReference('user-2', User::class));
       $group3->addTeammate($this->getReference('user-4', User::class));
       $this->addReference('group-3', $group3);


        // CONVERSATION

        $conversation = new Conversation();
        $conversation->setName($this->faker->sentence(3));
        $conversation->setPrivateGroup($this->getReference('group-2', Group::class));
        $conversation->getPrivateGroup()->setConversation($conversation);
        $conversation->addParticipant($conversation->getPrivateGroup()->getOwner());
        foreach ($conversation->getPrivateGroup()->getTeammate() as $teammate) {
            $conversation->addParticipant($teammate);
        }

        // Add 10 messages
        for ($i = 0; $i < 10; $i++) {
            $message = new Message();
            $message->setContent($this->faker->text(200));
            $message->setSender($group->getTeammate()->get(mt_rand(0, 2)));
            $message->setConversation($conversation);
            $conversation->addParticipant($message->getSender());

            $manager->persist($message);
        }

        $group->setConversation($conversation);
        $manager->persist($group);

        $manager->flush();


    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
