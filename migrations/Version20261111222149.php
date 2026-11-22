<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111222149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_item DROP CONSTRAINT fk_922e876f742ee4b');
        $this->addSql('DROP INDEX idx_922e876f742ee4b');
        $this->addSql('ALTER TABLE reservation_item DROP fk_assigned_equipment_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_item ADD fk_assigned_equipment_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation_item ADD CONSTRAINT fk_922e876f742ee4b FOREIGN KEY (fk_assigned_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_922e876f742ee4b ON reservation_item (fk_assigned_equipment_id)');
    }
}
