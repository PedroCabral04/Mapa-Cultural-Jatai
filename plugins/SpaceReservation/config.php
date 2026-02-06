<?php

return [
    // Configurações padrão do plugin
    'defaults' => [
        'min_notice_days' => 2,
        'max_advance_days' => 90,
    ],

    // Notificações habilitadas
    'notifications' => [
        'new_reservation' => true,
        'status_change' => true,
    ],
];
