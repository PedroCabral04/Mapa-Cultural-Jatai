<?php
/**
 * Configuração de reservas na página de edição do espaço (BaseV2)
 * Renderizado como uma aba dentro de <mc-tabs> via hook template(space.edit.tabs):end
 * 
 * A entidade é acessível via variável Vue reativa "entity"
 */

use MapasCulturais\i;
?>
<mc-tab label="<?php i::_e('Reservas'); ?>" slug="reservas">
    <mc-container>
        <mc-card class="feature">
            <template #title>
                <label class="card__title--title"><?php i::_e('Configurações de Reserva'); ?></label>
                <p class="card__title--description"><?php i::_e('Configure as opções de reserva para este espaço'); ?></p>
            </template>
            <template #content>
                <div class="grid-12">
                    <entity-field :entity="entity" classes="col-12" type="checkbox" prop="reservation_enabled" label="<?php i::esc_attr_e('Permitir reservas neste espaço'); ?>"></entity-field>
                    
                    <div v-if="entity.reservation_enabled" class="col-12 grid-12">
                        <entity-field :entity="entity" classes="col-12" prop="reservation_instructions" label="<?php i::esc_attr_e('Instruções para reserva'); ?>"></entity-field>
                        <entity-field :entity="entity" classes="col-4 sm:col-12" prop="reservation_max_capacity" label="<?php i::esc_attr_e('Capacidade máxima (0 = sem limite)'); ?>"></entity-field>
                        <entity-field :entity="entity" classes="col-4 sm:col-12" prop="reservation_min_notice_days" label="<?php i::esc_attr_e('Dias mínimos de antecedência'); ?>"></entity-field>
                        <entity-field :entity="entity" classes="col-4 sm:col-12" prop="reservation_max_advance_days" label="<?php i::esc_attr_e('Dias máximos de antecedência'); ?>"></entity-field>
                    </div>
                </div>
            </template>
        </mc-card>
    </mc-container>
</mc-tab>
