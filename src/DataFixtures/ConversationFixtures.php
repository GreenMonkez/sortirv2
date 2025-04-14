<?php

namespace App\DataFixtures;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ConversationFixtures extends Fixture implements DependentFixtureInterface
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        // Create 4 conversations
        for ($i = 1; $i <= 4; $i++) {
            $conversation = new Conversation();
            $conversation->setName($this->faker->sentence(3));
            $conversation->setCreator($this->getReference('user-'.mt_rand(0, 9), User::class));
            $conversation->addParticipant($conversation->getCreator());
            // Add 10 messages
            for ($i = 0; $i < 10; $i++) {
                $message = new Message();
                $message->setContent($this->faker->text(200));
                $message->setSender($this->getReference('user-'.mt_rand(0, 9), User::class));
                $message->setConversation($conversation);
                $conversation->addParticipant($message->getSender());

                $manager->persist($message);
            }

            $manager->persist($conversation);
        }

        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
