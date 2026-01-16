<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116080939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mensage (id INT AUTO_INCREMENT NOT NULL, contenido LONGTEXT NOT NULL, fecha_creacion DATETIME NOT NULL, leido_por LONGTEXT DEFAULT NULL, autor_id INT NOT NULL, sala_id INT NOT NULL, INDEX IDX_2ECD634C14D45BBE (autor_id), INDEX IDX_2ECD634CC51CDF3F (sala_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sala (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, activa TINYINT NOT NULL, fecha_creacion DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sala_user (sala_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_AA4CE0BAC51CDF3F (sala_id), INDEX IDX_AA4CE0BAA76ED395 (user_id), PRIMARY KEY (sala_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, correo VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, token_autenticacion VARCHAR(255) DEFAULT NULL, latitud DOUBLE PRECISION DEFAULT NULL, longitud DOUBLE PRECISION DEFAULT NULL, fecha_actualizacion_ubicacion VARCHAR(255) DEFAULT NULL, estado TINYINT NOT NULL, UNIQUE INDEX UNIQ_8D93D64977040BC9 (correo), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE usuario_bloqueado (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_8F201BF13AD8644E (user_source), INDEX IDX_8F201BF1233D34C1 (user_target), PRIMARY KEY (user_source, user_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mensage ADD CONSTRAINT FK_2ECD634C14D45BBE FOREIGN KEY (autor_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mensage ADD CONSTRAINT FK_2ECD634CC51CDF3F FOREIGN KEY (sala_id) REFERENCES sala (id)');
        $this->addSql('ALTER TABLE sala_user ADD CONSTRAINT FK_AA4CE0BAC51CDF3F FOREIGN KEY (sala_id) REFERENCES sala (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sala_user ADD CONSTRAINT FK_AA4CE0BAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE usuario_bloqueado ADD CONSTRAINT FK_8F201BF13AD8644E FOREIGN KEY (user_source) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE usuario_bloqueado ADD CONSTRAINT FK_8F201BF1233D34C1 FOREIGN KEY (user_target) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mensage DROP FOREIGN KEY FK_2ECD634C14D45BBE');
        $this->addSql('ALTER TABLE mensage DROP FOREIGN KEY FK_2ECD634CC51CDF3F');
        $this->addSql('ALTER TABLE sala_user DROP FOREIGN KEY FK_AA4CE0BAC51CDF3F');
        $this->addSql('ALTER TABLE sala_user DROP FOREIGN KEY FK_AA4CE0BAA76ED395');
        $this->addSql('ALTER TABLE usuario_bloqueado DROP FOREIGN KEY FK_8F201BF13AD8644E');
        $this->addSql('ALTER TABLE usuario_bloqueado DROP FOREIGN KEY FK_8F201BF1233D34C1');
        $this->addSql('DROP TABLE mensage');
        $this->addSql('DROP TABLE sala');
        $this->addSql('DROP TABLE sala_user');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE usuario_bloqueado');
    }
}
