<?php
/**
 * Inicializacao do componente space-reservation-management
 */

$app = \MapasCulturais\App::i();
$user = $app->user;

$this->jsObject['config']['spaceReservationManagement'] = [
    'baseURL' => $app->getBaseUrl(),
    'isLoggedIn' => !$user->is('guest'),
];
