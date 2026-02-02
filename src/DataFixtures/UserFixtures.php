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
        // Usuario administrador
        $admin = new User();
        $admin->setNombre('Administrador');
        $admin->setCorreo('admin@proyecto.com');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin1234')
        );
        $admin->setEstado(true);
        $admin->setTokenAutenticacion(null);
        $manager->persist($admin);

        // Usuarios adicionales para testing de chat privado
        $usuarios = [
            ['nombre' => 'María García', 'correo' => 'maria@test.com', 'password' => '123456'],
            ['nombre' => 'Carlos Rodriguez', 'correo' => 'carlos@test.com', 'password' => '123456'],
            ['nombre' => 'Ana Martínez', 'correo' => 'ana@test.com', 'password' => '123456'],
            ['nombre' => 'Luis Fernández', 'correo' => 'luis@test.com', 'password' => '123456'],
            ['nombre' => 'Laura López', 'correo' => 'laura@test.com', 'password' => '123456'],
            ['nombre' => 'David Sánchez', 'correo' => 'david@test.com', 'password' => '123456'],
            ['nombre' => 'Elena Ruiz', 'correo' => 'elena@test.com', 'password' => '123456'],
            ['nombre' => 'Miguel Torres', 'correo' => 'miguel@test.com', 'password' => '123456'],
            ['nombre' => 'Carmen Díaz', 'correo' => 'carmen@test.com', 'password' => '123456'],
            ['nombre' => 'Javier Moreno', 'correo' => 'javier@test.com', 'password' => '123456'],
            ['nombre' => 'Patricia Jiménez', 'correo' => 'patricia@test.com', 'password' => '123456'],
            ['nombre' => 'Roberto Álvarez', 'correo' => 'roberto@test.com', 'password' => '123456'],
            ['nombre' => 'Silvia Romero', 'correo' => 'silvia@test.com', 'password' => '123456'],
            ['nombre' => 'Fernando Navarro', 'correo' => 'fernando@test.com', 'password' => '123456'],
            ['nombre' => 'Isabel Vega', 'correo' => 'isabel@test.com', 'password' => '123456'],
            ['nombre' => 'Andrés Herrera', 'correo' => 'andres@test.com', 'password' => '123456'],
            ['nombre' => 'Mónica Castro', 'correo' => 'monica@test.com', 'password' => '123456'],
            ['nombre' => 'Rafael Ortega', 'correo' => 'rafael@test.com', 'password' => '123456'],
            ['nombre' => 'Cristina Delgado', 'correo' => 'cristina@test.com', 'password' => '123456'],
            ['nombre' => 'Alejandro Peña', 'correo' => 'alejandro@test.com', 'password' => '123456'],
            ['nombre' => 'Beatriz Serrano', 'correo' => 'beatriz@test.com', 'password' => '123456'],
            ['nombre' => 'Sergio Mendoza', 'correo' => 'sergio@test.com', 'password' => '123456'],
            ['nombre' => 'Natalia Campos', 'correo' => 'natalia@test.com', 'password' => '123456'],
            ['nombre' => 'Rubén Flores', 'correo' => 'ruben@test.com', 'password' => '123456'],
            ['nombre' => 'Gloria Aguilar', 'correo' => 'gloria@test.com', 'password' => '123456']
        ];

        foreach ($usuarios as $userData) {
            $user = new User();
            $user->setNombre($userData['nombre']);
            $user->setCorreo($userData['correo']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $userData['password'])
            );
            $user->setEstado(true);
            $user->setTokenAutenticacion(null);
            
            $manager->persist($user);
        }

        $manager->flush();
    }
}
