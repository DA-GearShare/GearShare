<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005174232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE condition_photo (pk_condition_photo_id UUID NOT NULL, phase VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_reservation_id UUID NOT NULL, fk_take_by_user_id UUID DEFAULT NULL, PRIMARY KEY (pk_condition_photo_id))');
        $this->addSql('CREATE INDEX IDX_586C6013CD3C7E0E ON condition_photo (fk_reservation_id)');
        $this->addSql('CREATE INDEX IDX_586C60131E199D07 ON condition_photo (fk_take_by_user_id)');
        $this->addSql('CREATE TABLE equipment (pk_equipment_id UUID NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, model VARCHAR(255) NOT NULL, code VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, fk_pool_id UUID DEFAULT NULL, PRIMARY KEY (pk_equipment_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D338D58377153098 ON equipment (code)');
        $this->addSql('CREATE INDEX IDX_D338D5838B1B3BF3 ON equipment (fk_pool_id)');
        $this->addSql('CREATE TABLE equipment_accessory (pk_equipment_id UUID NOT NULL, pk_accessory_equipment_id UUID NOT NULL, PRIMARY KEY (pk_equipment_id, pk_accessory_equipment_id))');
        $this->addSql('CREATE INDEX IDX_738C6365163AA05A ON equipment_accessory (pk_equipment_id)');
        $this->addSql('CREATE INDEX IDX_738C63654FC760BB ON equipment_accessory (pk_accessory_equipment_id)');
        $this->addSql('CREATE TABLE equipment_photo (pk_equipment_photo_id UUID NOT NULL, url VARCHAR(255) NOT NULL, is_main BOOLEAN DEFAULT false NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_equipment_id UUID NOT NULL, PRIMARY KEY (pk_equipment_photo_id))');
        $this->addSql('CREATE INDEX IDX_FDEEA5FC70A2EBE7 ON equipment_photo (fk_equipment_id)');
        $this->addSql('CREATE TABLE equipment_pool (pk_pool_id UUID NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (pk_pool_id))');
        $this->addSql('CREATE TABLE notification_log (pk_notification_id UUID NOT NULL, template_name VARCHAR(255) NOT NULL, send_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_user_id UUID DEFAULT NULL, fk_reservation_id UUID DEFAULT NULL, PRIMARY KEY (pk_notification_id))');
        $this->addSql('CREATE INDEX IDX_ED15DF25741EEB9 ON notification_log (fk_user_id)');
        $this->addSql('CREATE INDEX IDX_ED15DF2CD3C7E0E ON notification_log (fk_reservation_id)');
        $this->addSql('CREATE TABLE opening_hours_exception (pk_opening_hours_exception_id UUID NOT NULL, exception_date DATE NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, is_closed BOOLEAN DEFAULT false NOT NULL, valid_until DATE NOT NULL, fk_created_by UUID NOT NULL, PRIMARY KEY (pk_opening_hours_exception_id))');
        $this->addSql('CREATE INDEX IDX_E0F90DC72E7C61C5 ON opening_hours_exception (fk_created_by)');
        $this->addSql('CREATE TABLE opening_hours_recurring (pk_opening_hours_recurring_id UUID NOT NULL, weekday INT NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, valid_from DATE NOT NULL, valid_until DATE NOT NULL, fk_created_by UUID NOT NULL, PRIMARY KEY (pk_opening_hours_recurring_id))');
        $this->addSql('CREATE INDEX IDX_F35C81472E7C61C5 ON opening_hours_recurring (fk_created_by)');
        $this->addSql('CREATE TABLE reservation (pk_reservation_id UUID NOT NULL, status VARCHAR(255) NOT NULL, start_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_requested_by_user_id UUID DEFAULT NULL, fk_responsible_user_id UUID DEFAULT NULL, PRIMARY KEY (pk_reservation_id))');
        $this->addSql('CREATE INDEX IDX_42C849554B71405 ON reservation (fk_requested_by_user_id)');
        $this->addSql('CREATE INDEX IDX_42C849556BC8123D ON reservation (fk_responsible_user_id)');
        $this->addSql('CREATE TABLE reservation_change_log (pk_reservation_change_id UUID NOT NULL, old_start_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, old_end_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, new_start_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, new_end_datetime TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, changed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_reservation_id UUID DEFAULT NULL, fk_changed_by_user_id UUID DEFAULT NULL, PRIMARY KEY (pk_reservation_change_id))');
        $this->addSql('CREATE INDEX IDX_3EB0C36CCD3C7E0E ON reservation_change_log (fk_reservation_id)');
        $this->addSql('CREATE INDEX IDX_3EB0C36C71A7B0BC ON reservation_change_log (fk_changed_by_user_id)');
        $this->addSql('CREATE TABLE reservation_item (pk_reservation_item_id UUID NOT NULL, status VARCHAR(255) NOT NULL, issued_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, fk_reservation_id UUID NOT NULL, fk_requested_equipment_id UUID DEFAULT NULL, fk_requested_pool_id UUID DEFAULT NULL, fk_assigned_equipment_id UUID DEFAULT NULL, PRIMARY KEY (pk_reservation_item_id))');
        $this->addSql('CREATE INDEX IDX_922E876CD3C7E0E ON reservation_item (fk_reservation_id)');
        $this->addSql('CREATE INDEX IDX_922E876B1FB06CB ON reservation_item (fk_requested_equipment_id)');
        $this->addSql('CREATE INDEX IDX_922E87677CF9163 ON reservation_item (fk_requested_pool_id)');
        $this->addSql('CREATE INDEX IDX_922E876F742EE4B ON reservation_item (fk_assigned_equipment_id)');
        $this->addSql('CREATE TABLE reservation_transfer_log (pk_reservation_transfer_id UUID NOT NULL, transferred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, fk_reservation_id UUID DEFAULT NULL, fk_by_user_id UUID DEFAULT NULL, fk_to_user_id UUID DEFAULT NULL, PRIMARY KEY (pk_reservation_transfer_id))');
        $this->addSql('CREATE INDEX IDX_C0C0BF65CD3C7E0E ON reservation_transfer_log (fk_reservation_id)');
        $this->addSql('CREATE INDEX IDX_C0C0BF652CF2EEA7 ON reservation_transfer_log (fk_by_user_id)');
        $this->addSql('CREATE INDEX IDX_C0C0BF65D99824F3 ON reservation_transfer_log (fk_to_user_id)');
        $this->addSql('CREATE TABLE "user" (user_id UUID NOT NULL, display_name VARCHAR(255) DEFAULT NULL, is_admin BOOLEAN DEFAULT false NOT NULL, is_teacher BOOLEAN DEFAULT false NOT NULL, oauth_user_id VARCHAR(64) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY (user_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649B4687CE0 ON "user" (oauth_user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE condition_photo ADD CONSTRAINT FK_586C6013CD3C7E0E FOREIGN KEY (fk_reservation_id) REFERENCES reservation (pk_reservation_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE condition_photo ADD CONSTRAINT FK_586C60131E199D07 FOREIGN KEY (fk_take_by_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE equipment ADD CONSTRAINT FK_D338D5838B1B3BF3 FOREIGN KEY (fk_pool_id) REFERENCES equipment_pool (pk_pool_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE equipment_accessory ADD CONSTRAINT FK_738C6365163AA05A FOREIGN KEY (pk_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE equipment_accessory ADD CONSTRAINT FK_738C63654FC760BB FOREIGN KEY (pk_accessory_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE equipment_photo ADD CONSTRAINT FK_FDEEA5FC70A2EBE7 FOREIGN KEY (fk_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notification_log ADD CONSTRAINT FK_ED15DF25741EEB9 FOREIGN KEY (fk_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notification_log ADD CONSTRAINT FK_ED15DF2CD3C7E0E FOREIGN KEY (fk_reservation_id) REFERENCES reservation (pk_reservation_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE opening_hours_exception ADD CONSTRAINT FK_E0F90DC72E7C61C5 FOREIGN KEY (fk_created_by) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE opening_hours_recurring ADD CONSTRAINT FK_F35C81472E7C61C5 FOREIGN KEY (fk_created_by) REFERENCES "user" (user_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849554B71405 FOREIGN KEY (fk_requested_by_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556BC8123D FOREIGN KEY (fk_responsible_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_change_log ADD CONSTRAINT FK_3EB0C36CCD3C7E0E FOREIGN KEY (fk_reservation_id) REFERENCES reservation (pk_reservation_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_change_log ADD CONSTRAINT FK_3EB0C36C71A7B0BC FOREIGN KEY (fk_changed_by_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_item ADD CONSTRAINT FK_922E876CD3C7E0E FOREIGN KEY (fk_reservation_id) REFERENCES reservation (pk_reservation_id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_item ADD CONSTRAINT FK_922E876B1FB06CB FOREIGN KEY (fk_requested_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_item ADD CONSTRAINT FK_922E87677CF9163 FOREIGN KEY (fk_requested_pool_id) REFERENCES equipment_pool (pk_pool_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_item ADD CONSTRAINT FK_922E876F742EE4B FOREIGN KEY (fk_assigned_equipment_id) REFERENCES equipment (pk_equipment_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_transfer_log ADD CONSTRAINT FK_C0C0BF65CD3C7E0E FOREIGN KEY (fk_reservation_id) REFERENCES reservation (pk_reservation_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_transfer_log ADD CONSTRAINT FK_C0C0BF652CF2EEA7 FOREIGN KEY (fk_by_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE reservation_transfer_log ADD CONSTRAINT FK_C0C0BF65D99824F3 FOREIGN KEY (fk_to_user_id) REFERENCES "user" (user_id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE condition_photo DROP CONSTRAINT FK_586C6013CD3C7E0E');
        $this->addSql('ALTER TABLE condition_photo DROP CONSTRAINT FK_586C60131E199D07');
        $this->addSql('ALTER TABLE equipment DROP CONSTRAINT FK_D338D5838B1B3BF3');
        $this->addSql('ALTER TABLE equipment_accessory DROP CONSTRAINT FK_738C6365163AA05A');
        $this->addSql('ALTER TABLE equipment_accessory DROP CONSTRAINT FK_738C63654FC760BB');
        $this->addSql('ALTER TABLE equipment_photo DROP CONSTRAINT FK_FDEEA5FC70A2EBE7');
        $this->addSql('ALTER TABLE notification_log DROP CONSTRAINT FK_ED15DF25741EEB9');
        $this->addSql('ALTER TABLE notification_log DROP CONSTRAINT FK_ED15DF2CD3C7E0E');
        $this->addSql('ALTER TABLE opening_hours_exception DROP CONSTRAINT FK_E0F90DC72E7C61C5');
        $this->addSql('ALTER TABLE opening_hours_recurring DROP CONSTRAINT FK_F35C81472E7C61C5');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C849554B71405');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C849556BC8123D');
        $this->addSql('ALTER TABLE reservation_change_log DROP CONSTRAINT FK_3EB0C36CCD3C7E0E');
        $this->addSql('ALTER TABLE reservation_change_log DROP CONSTRAINT FK_3EB0C36C71A7B0BC');
        $this->addSql('ALTER TABLE reservation_item DROP CONSTRAINT FK_922E876CD3C7E0E');
        $this->addSql('ALTER TABLE reservation_item DROP CONSTRAINT FK_922E876B1FB06CB');
        $this->addSql('ALTER TABLE reservation_item DROP CONSTRAINT FK_922E87677CF9163');
        $this->addSql('ALTER TABLE reservation_item DROP CONSTRAINT FK_922E876F742EE4B');
        $this->addSql('ALTER TABLE reservation_transfer_log DROP CONSTRAINT FK_C0C0BF65CD3C7E0E');
        $this->addSql('ALTER TABLE reservation_transfer_log DROP CONSTRAINT FK_C0C0BF652CF2EEA7');
        $this->addSql('ALTER TABLE reservation_transfer_log DROP CONSTRAINT FK_C0C0BF65D99824F3');
        $this->addSql('DROP TABLE condition_photo');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE equipment_accessory');
        $this->addSql('DROP TABLE equipment_photo');
        $this->addSql('DROP TABLE equipment_pool');
        $this->addSql('DROP TABLE notification_log');
        $this->addSql('DROP TABLE opening_hours_exception');
        $this->addSql('DROP TABLE opening_hours_recurring');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_change_log');
        $this->addSql('DROP TABLE reservation_item');
        $this->addSql('DROP TABLE reservation_transfer_log');
        $this->addSql('DROP TABLE "user"');
    }
}
