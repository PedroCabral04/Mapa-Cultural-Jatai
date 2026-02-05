<?php
/**
 * Campos de configuração de reservas no formulário do espaço
 * @var \MapasCulturais\Entities\Space|null $entity
 */

// Valores padrão quando criando novo espaço
$enabled = $entity ? $entity->reservation_enabled : false;
$instructions = $entity ? $entity->reservation_instructions : '';
$maxCapacity = $entity ? $entity->reservation_max_capacity : 0;
$minNotice = ($entity && $entity->reservation_min_notice_days) ? $entity->reservation_min_notice_days : 2;
$maxAdvance = ($entity && $entity->reservation_max_advance_days) ? $entity->reservation_max_advance_days : 90;
?>
<div class="space-reservation-config">
    <h3><?php \MapasCulturais\i::_e('Configurações de Reserva'); ?></h3>

    <div class="form-group">
        <label>
            <input type="checkbox" name="reservation_enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
            <?php \MapasCulturais\i::_e('Permitir reservas neste espaço'); ?>
        </label>
    </div>

    <div class="reservation-fields" style="<?php echo $enabled ? '' : 'display: none;'; ?>">
        <div class="form-group">
            <label><?php \MapasCulturais\i::_e('Instruções para reserva:'); ?></label>
            <textarea name="reservation_instructions" class="form-control" rows="3"
                      placeholder="<?php \MapasCulturais\i::_e('Informe aqui instruções específicas para quem deseja reservar este espaço...'); ?>"><?php echo htmlspecialchars($instructions); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label><?php \MapasCulturais\i::_e('Capacidade máxima:'); ?></label>
                <input type="number" name="reservation_max_capacity" class="form-control"
                       value="<?php echo $maxCapacity; ?>" min="0">
                <small><?php \MapasCulturais\i::_e('0 = sem limite'); ?></small>
            </div>

            <div class="form-group">
                <label><?php \MapasCulturais\i::_e('Dias mínimos de antecedência:'); ?></label>
                <input type="number" name="reservation_min_notice_days" class="form-control"
                       value="<?php echo $minNotice; ?>" min="0">
            </div>

            <div class="form-group">
                <label><?php \MapasCulturais\i::_e('Dias máximos de antecedência:'); ?></label>
                <input type="number" name="reservation_max_advance_days" class="form-control"
                       value="<?php echo $maxAdvance; ?>" min="1">
            </div>
        </div>
    </div>
</div>

<script>
// Toggle campos de configuração
jQuery(function($) {
    $('input[name="reservation_enabled"]').on('change', function() {
        $('.reservation-fields').toggle(this.checked);
    });
});
</script>
