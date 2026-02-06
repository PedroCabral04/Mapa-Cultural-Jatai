<?php

use MapasCulturais\i;

$this->import('mc-icon space-reservation-management');
?>

<div class="panel-page">
    <header class="panel-page__header">
        <div class="panel-page__header-title">
            <div class="title">
                <div class="title__icon default"><mc-icon name="event"></mc-icon></div>
                <h1 class="title__title"><?= i::__('Gestão de Reservas') ?></h1>
            </div>
        </div>
        <p class="panel-page__header-subtitle">
            <?= i::__('Gerencie reservas de espaços por status e aprove ou rejeite solicitações.') ?>
        </p>
    </header>

    <space-reservation-management></space-reservation-management>
</div>
