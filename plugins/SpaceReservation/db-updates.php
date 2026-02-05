<?php

use function MapasCulturais\__exec;
use function MapasCulturais\__table_exists;

return [
    'create space_reservation table' => function () {
        if (!__table_exists('space_reservation')) {
            // Cria sequence para o ID
            __exec("CREATE SEQUENCE IF NOT EXISTS space_reservation_id_seq START 1");

            // Cria tabela
            __exec("CREATE TABLE space_reservation (
                id INTEGER NOT NULL DEFAULT nextval('space_reservation_id_seq'),
                space_id INTEGER NOT NULL,
                requester_id INTEGER NOT NULL,
                start_time TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                end_time TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                purpose TEXT,
                num_people INTEGER,
                special_requirements TEXT,
                rejection_reason TEXT,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP WITHOUT TIME ZONE,
                PRIMARY KEY (id)
            )");

            // Cria índices
            __exec("CREATE INDEX idx_reservation_space ON space_reservation(space_id)");
            __exec("CREATE INDEX idx_reservation_requester ON space_reservation(requester_id)");
            __exec("CREATE INDEX idx_reservation_status ON space_reservation(status)");
            __exec("CREATE INDEX idx_reservation_time ON space_reservation(start_time, end_time)");
            __exec("CREATE INDEX idx_reservation_created ON space_reservation(created_at)");

            // Cria foreign keys
            __exec("ALTER TABLE space_reservation
                ADD CONSTRAINT fk_reservation_space
                FOREIGN KEY (space_id) REFERENCES space(id) ON DELETE CASCADE");

            __exec("ALTER TABLE space_reservation
                ADD CONSTRAINT fk_reservation_requester
                FOREIGN KEY (requester_id) REFERENCES agent(id) ON DELETE CASCADE");

            // Constraint para status válido
            __exec("ALTER TABLE space_reservation
                ADD CONSTRAINT chk_reservation_status
                CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'))");
        }
    },

];
