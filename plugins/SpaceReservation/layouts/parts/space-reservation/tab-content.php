<?php
/**
 * Conteúdo da aba de Reservas
 * @var \MapasCulturais\Entities\Space $entity
 */

$instructions = $entity->reservation_instructions;
$maxCapacity = $entity->reservation_max_capacity;
$minNotice = $entity->reservation_min_notice_days ?: 2;
$maxAdvance = $entity->reservation_max_advance_days ?: 90;
?>
<div id="reservas" class="aba-content tab-content">
    <div class="space-reservation-container">
        <h2><?php \MapasCulturais\i::_e('Reservar este espaço'); ?></h2>

        <?php if ($instructions): ?>
            <div class="reservation-instructions alert info">
                <strong><?php \MapasCulturais\i::_e('Instruções:'); ?></strong>
                <p><?php echo nl2br(htmlspecialchars($instructions)); ?></p>
            </div>
        <?php endif; ?>

        <div class="reservation-info">
            <?php if ($maxCapacity): ?>
                <p>
                    <strong><?php \MapasCulturais\i::_e('Capacidade:'); ?></strong>
                    <?php echo $maxCapacity; ?> <?php \MapasCulturais\i::_e('pessoas'); ?>
                </p>
            <?php endif; ?>
            <p>
                <strong><?php \MapasCulturais\i::_e('Antecedência:'); ?></strong>
                <?php
                printf(
                    \MapasCulturais\i::__('mínimo %d dias, máximo %d dias'),
                    $minNotice,
                    $maxAdvance
                );
                ?>
            </p>
        </div>

        <?php
        // Injeta o componente Vue do calendário
        $this->part('space-reservation/calendar-component', ['entity' => $entity]);
        ?>
    </div>
</div>
