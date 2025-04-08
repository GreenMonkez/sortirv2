<?php

namespace App\DataFixtures;

use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hashI;
    private $faker;

    public function __construct(
        UserPasswordHasherInterface $hashI
    ){
        $this->hashI = $hashI;
        $this->faker = Faker\Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setPseudo($this->faker->userName);
        $admin->setEmail($this->faker->email);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hashI->hashPassword($admin, 'password'));
        $admin->setFirstName($this->faker->firstName);
        $admin->setLastName($this->faker->lastName);
        $admin->setPhoneNumber($this->faker->phoneNumber);
        $admin->setIsActive(true);
        $admin->setSite($this->getReference('site-'.mt_rand(1,4), Site::class));
        $manager->persist($admin);

        // Create regular users
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setPseudo($this->faker->userName);
            $user->setEmail($this->faker->email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->hashI->hashPassword($user, 'password'));
            $user->setFirstName($this->faker->firstName);
            $user->setLastName($this->faker->lastName);
            $user->setPhoneNumber($this->faker->phoneNumber);
            $user->setIsActive($this->faker->boolean(80));
            $user->setSite($this->getReference('site-'.mt_rand(1,4), Site::class));
            $manager->persist($user);
        }

        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [
            SiteFixtures::class,
        ];
    }
}
