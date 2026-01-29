<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260129000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega el campo accesos_usuarios a la tabla sala para rastrear cuándo cada usuario accede al chat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sala ADD accesos_usuarios JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sala DROP accesos_usuarios');
    }
}
