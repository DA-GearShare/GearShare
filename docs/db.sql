CREATE TABLE IF NOT EXISTS "user"
(
    "user_id" UUID         NOT NULL,
    "email"           VARCHAR(255) NOT NULL UNIQUE,
    "display_name"    VARCHAR(255),
    "is_admin"        BOOLEAN      NOT NULL DEFAULT false,
    "is_teacher"      BOOLEAN      NOT NULL DEFAULT false,
    PRIMARY KEY ("user_id")
);

CREATE TABLE IF NOT EXISTS "equipment"
(
    "pk_equipment_id" UUID         NOT NULL,
    "fk_pool_id"      UUID,
    "name"            VARCHAR(255) NOT NULL,
    "location"        VARCHAR(255) NOT NULL,
    "type"            VARCHAR(255) NOT NULL,
    "model"           VARCHAR(255) NOT NULL,
    "code"            VARCHAR(255) UNIQUE,
    "status"          VARCHAR(255) NOT NULL,
    "created_at"      TIMESTAMP    NOT NULL,
    "deleted_at"      TIMESTAMP,
    PRIMARY KEY ("pk_equipment_id")
);

CREATE TABLE IF NOT EXISTS "equipment_photo"
(
    "pk_equipment_photo_id" UUID         NOT NULL,
    "fk_equipment_id"       UUID         NOT NULL,
    "url"                   VARCHAR(255) NOT NULL,
    "is_main"               BOOLEAN      NOT NULL DEFAULT false,
    "uploaded_at"           TIMESTAMP    NOT NULL,
    PRIMARY KEY ("pk_equipment_photo_id")
);

CREATE TABLE IF NOT EXISTS "equipment_accessory"
(
    "pk_equipment_id"           UUID NOT NULL,
    "pk_accessory_equipment_id" UUID NOT NULL,
    PRIMARY KEY ("pk_equipment_id", "pk_accessory_equipment_id")
);

CREATE TABLE IF NOT EXISTS "equipment_pool"
(
    "pk_pool_id"  UUID         NOT NULL,
    "name"        VARCHAR(255) NOT NULL,
    "description" VARCHAR(255),
    "created_at"  TIMESTAMP    NOT NULL,
    PRIMARY KEY ("pk_pool_id")
);

CREATE TABLE IF NOT EXISTS "reservation_item"
(
    "pk_reservation_item_id"    UUID         NOT NULL,
    "fk_reservation_id"         UUID         NOT NULL,
    "fk_requested_equipment_id" UUID,
    "fk_requested_pool_id"      UUID,
    "fk_assigned_equipment_id"  UUID,
    "status"                    VARCHAR(255) NOT NULL,
    "issued_at"                 TIMESTAMP,
    "returned_at"               TIMESTAMP,
    PRIMARY KEY ("pk_reservation_item_id")
);

CREATE TABLE IF NOT EXISTS "reservation"
(
    "pk_reservation_id"       UUID         NOT NULL,
    "fk_requested_by_user_id" UUID,
    "fk_responsible_user_id"  UUID,
    "status"                  VARCHAR(255) NOT NULL,
    "start_datetime"          TIMESTAMP    NOT NULL,
    "end_datetime"            TIMESTAMP    NOT NULL,
    "created_at"              TIMESTAMP    NOT NULL,
    PRIMARY KEY ("pk_reservation_id")
);

CREATE TABLE IF NOT EXISTS "condition_photo"
(
    "pk_condition_photo_id" UUID         NOT NULL,
    "fk_reservation_id"     UUID         NOT NULL,
    "fk_take_by_user_id"    UUID,
    "phase"                 VARCHAR(255) NOT NULL,
    "url"                   VARCHAR(255) NOT NULL,
    "created_at"            TIMESTAMP    NOT NULL,
    PRIMARY KEY ("pk_condition_photo_id")
);

CREATE TABLE IF NOT EXISTS "opening_hours_recurring"
(
    "pk_opening_hours_recurring_id" UUID    NOT NULL,
    "weekday"                       INTEGER NOT NULL,
    "start_time"                    TIME    NOT NULL,
    "end_time"                      TIME    NOT NULL,
    "valid_from"                    DATE    NOT NULL,
    "valid_until"                   DATE    NOT NULL,
    "fk_created_by"                 UUID    NOT NULL,
    PRIMARY KEY ("pk_opening_hours_recurring_id")
);

CREATE TABLE IF NOT EXISTS "opening_hours_exeption"
(
    "pk_opening_hours_exeption_id" UUID    NOT NULL,
    "exception_date"               DATE    NOT NULL,
    "start_time"                   TIME    NOT NULL,
    "end_time"                     TIME    NOT NULL,
    "is_closed"                    BOOLEAN NOT NULL DEFAULT false,
    "valid_until"                  DATE    NOT NULL,
    "fk_created_by"                UUID    NOT NULL,
    PRIMARY KEY ("pk_opening_hours_exeption_id")
);

CREATE TABLE IF NOT EXISTS "reservation_change_log"
(
    "pk_reservation_change_id" UUID      NOT NULL,
    "fk_reservation_id"        UUID,
    "fk_changed_by_user_id"    UUID,
    "old_start_datetime"       TIMESTAMP NOT NULL,
    "old_end_datetime"         TIMESTAMP NOT NULL,
    "new_start_datetime"       TIMESTAMP NOT NULL,
    "new_end_datetime"         TIMESTAMP NOT NULL,
    "changed_at"               TIMESTAMP NOT NULL,
    PRIMARY KEY ("pk_reservation_change_id")
);

CREATE TABLE IF NOT EXISTS "reservation_transfer_log"
(
    "pk_reservation_transfer_id" UUID      NOT NULL,
    "fk_reservation_id"          UUID,
    "fk_by_user_id"              UUID,
    "fk_to_user_id"              UUID,
    "transferred_at"               TIMESTAMP NOT NULL,
    PRIMARY KEY ("pk_reservation_transfer_id")
);

CREATE TABLE IF NOT EXISTS "notification_log"
(
    "pk_notification_id" UUID         NOT NULL,
    "fk_user_id"         UUID,
    "fk_reservation_id"  UUID,
    "template_name"      VARCHAR(255) NOT NULL,
    "send_at"            TIMESTAMP    NOT NULL,
    PRIMARY KEY ("pk_notification_id")
);

ALTER TABLE "equipment_accessory"
    ADD FOREIGN KEY ("pk_accessory_equipment_id") REFERENCES "equipment" ("pk_equipment_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "equipment_accessory"
    ADD FOREIGN KEY ("pk_equipment_id") REFERENCES "equipment" ("pk_equipment_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "equipment_photo"
    ADD FOREIGN KEY ("fk_equipment_id") REFERENCES "equipment" ("pk_equipment_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "reservation"
    ADD FOREIGN KEY ("fk_requested_by_user_id") REFERENCES "user" ("user_id")
        ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE "reservation"
    ADD FOREIGN KEY ("fk_responsible_user_id") REFERENCES "user" ("user_id")
        ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE "reservation_item"
    ADD FOREIGN KEY ("fk_reservation_id") REFERENCES "reservation" ("pk_reservation_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "reservation_item"
    ADD FOREIGN KEY ("fk_requested_equipment_id") REFERENCES "equipment" ("pk_equipment_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_item"
    ADD FOREIGN KEY ("fk_requested_pool_id") REFERENCES "equipment_pool" ("pk_pool_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_item"
    ADD FOREIGN KEY ("fk_assigned_equipment_id") REFERENCES "equipment" ("pk_equipment_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "condition_photo"
    ADD FOREIGN KEY ("fk_reservation_id") REFERENCES "reservation" ("pk_reservation_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "condition_photo"
    ADD FOREIGN KEY ("fk_take_by_user_id") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "opening_hours_recurring"
    ADD FOREIGN KEY ("fk_created_by") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "opening_hours_exeption"
    ADD FOREIGN KEY ("fk_created_by") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE CASCADE;
ALTER TABLE "notification_log"
    ADD FOREIGN KEY ("fk_user_id") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "notification_log"
    ADD FOREIGN KEY ("fk_reservation_id") REFERENCES "reservation" ("pk_reservation_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_transfer_log"
    ADD FOREIGN KEY ("fk_reservation_id") REFERENCES "reservation" ("pk_reservation_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_transfer_log"
    ADD FOREIGN KEY ("fk_by_user_id") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_transfer_log"
    ADD FOREIGN KEY ("fk_to_user_id") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_change_log"
    ADD FOREIGN KEY ("fk_reservation_id") REFERENCES "reservation" ("pk_reservation_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "reservation_change_log"
    ADD FOREIGN KEY ("fk_changed_by_user_id") REFERENCES "user" ("user_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
ALTER TABLE "equipment"
    ADD FOREIGN KEY ("fk_pool_id") REFERENCES "equipment_pool" ("pk_pool_id")
        ON UPDATE NO ACTION ON DELETE SET NULL;
