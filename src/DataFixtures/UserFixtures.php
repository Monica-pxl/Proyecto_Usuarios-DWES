<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setNombre('Admin');
        $admin->setCorreo('admin@proyecto.com');

        // Hasheamos la contraseña
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin1234')
        );

        // Estado inicial: false (no autenticado aún)
        $admin->setEstado(false);

        // Sin token de autenticación inicial
        $admin->setTokenAutenticacion(null);

        $manager->persist($admin);

        $manager->flush();
    }
}
