<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class LoginFixtures extends Fixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    public function __construct (UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('Jaeger767');
        $user->setPassword('$argon2id$v=19$m=65536,t=4,p=1$smXwQaEIYixDkzcStXf+zA$LX96r2P709fe7Sf0/qxK4hbkuZIVnIPfWvddygvnQQE');
        $user->setRoles(['REDDIT_USER']);
        $manager->persist($user);

        $manager->flush();
    }
}
