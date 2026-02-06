<?php
/**
 * Aba de Reservas na página single do espaço (BaseV2)
 * Renderizado como <mc-tab> dentro de <mc-tabs> via hook template(space.single.tabs):end
 *
 * A entidade é acessível via variável Vue reativa "entity"
 */

use MapasCulturais\i;

$this->import('space-reservation-calendar space-reservation-management');
?>
<mc-tab v-if="entity.reservation_enabled" label="<?php i::_e('Reservas'); ?>" slug="reservas">
    <mc-container>
        <div class="space-reservation-container">
            <h2><?php i::_e('Reservar este espaço'); ?></h2>

            <div v-if="entity.reservation_instructions" class="reservation-instructions">
                <div class="calendar__alert calendar__alert--info">
                    <strong><?php i::_e('Instruções:'); ?></strong>
                    <span v-html="entity.reservation_instructions"></span>
                </div>
            </div>

            <div class="reservation-info" style="margin-bottom: 1rem;">
                <p v-if="entity.reservation_max_capacity" style="margin: 0.25rem 0;">
                    <strong><?php i::_e('Capacidade:'); ?></strong>
                    {{entity.reservation_max_capacity}} <?php i::_e('pessoas'); ?>
                </p>
                <p style="margin: 0.25rem 0;">
                    <strong><?php i::_e('Antecedência:'); ?></strong>
                    <?php i::_e('mínimo'); ?> {{entity.reservation_min_notice_days || 2}} <?php i::_e('dias, máximo'); ?> {{entity.reservation_max_advance_days || 90}} <?php i::_e('dias'); ?>
                </p>
            </div>

            <space-reservation-calendar :entity="entity"></space-reservation-calendar>
        </div>
    </mc-container>
</mc-tab>

<mc-tab v-if="entity.reservation_enabled && entity.currentUserPermissions && entity.currentUserPermissions['@control']" label="<?php i::_e('Gestão de Reservas'); ?>" slug="gestao-reservas">
    <mc-container>
        <space-reservation-management :entity="entity"></space-reservation-management>
    </mc-container>
</mc-tab>
