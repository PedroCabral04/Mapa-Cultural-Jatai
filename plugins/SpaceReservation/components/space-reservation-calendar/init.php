<?php
/**
 * Inicialização do componente space-reservation-calendar
 * Passa dados do servidor para o cliente via $MAPAS.config
 */

$app = \MapasCulturais\App::i();
$user = $app->user;

$this->jsObject['config']['spaceReservationCalendar'] = [
    'isLoggedIn' => !$user->is('guest'),
    'canRequest' => !$user->is('guest') && $user->profile && $user->profile->status >= 0,
    'baseURL' => $app->getBaseUrl(),
];
