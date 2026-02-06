<?php
/**
 * Componente do calendário de reservas
 * @var \MapasCulturais\Entities\Space $entity
 */

$app = \MapasCulturais\App::i();
$user = $app->user;
$canRequest = !$user->is('guest') && $user->profile && $user->profile->status >= 0;
?>
<div id="space-reservation-calendar" class="space-reservation-calendar"
     data-space-id="<?php echo $entity->id; ?>"
     data-can-request="<?php echo $canRequest ? 'true' : 'false'; ?>"
     data-logged-in="<?php echo $user->is('guest') ? 'false' : 'true'; ?>">

    <div class="calendar-header">
        <button class="btn btn-default prev-month">&lt;</button>
        <h3 class="current-month"></h3>
        <button class="btn btn-default next-month">&gt;</button>
    </div>

    <div class="calendar-grid">
        <div class="calendar-weekdays">
            <span><?php \MapasCulturais\i::_e('Dom'); ?></span>
            <span><?php \MapasCulturais\i::_e('Seg'); ?></span>
            <span><?php \MapasCulturais\i::_e('Ter'); ?></span>
            <span><?php \MapasCulturais\i::_e('Qua'); ?></span>
            <span><?php \MapasCulturais\i::_e('Qui'); ?></span>
            <span><?php \MapasCulturais\i::_e('Sex'); ?></span>
            <span><?php \MapasCulturais\i::_e('Sáb'); ?></span>
        </div>
        <div class="calendar-days"></div>
    </div>

    <div class="calendar-legend">
        <span class="legend-item">
            <span class="legend-color available"></span>
            <?php \MapasCulturais\i::_e('Disponível'); ?>
        </span>
        <span class="legend-item">
            <span class="legend-color occupied"></span>
            <?php \MapasCulturais\i::_e('Reservado'); ?>
        </span>
        <span class="legend-item">
            <span class="legend-color partial"></span>
            <?php \MapasCulturais\i::_e('Parcialmente ocupado'); ?>
        </span>
        <span class="legend-item">
            <span class="legend-color unavailable"></span>
            <?php \MapasCulturais\i::_e('Indisponível'); ?>
        </span>
    </div>

    <?php if (!$user->is('guest') && !$canRequest): ?>
        <div class="alert warning">
            <?php \MapasCulturais\i::_e('Você precisa ter um agente verificado para solicitar reservas.'); ?>
        </div>
    <?php elseif ($user->is('guest')): ?>
        <div class="alert info">
            <?php \MapasCulturais\i::_e('Faça login para solicitar uma reserva.'); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de solicitação de reserva -->
<div id="reservation-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php \MapasCulturais\i::_e('Solicitar Reserva'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="reservation-form">
                <input type="hidden" name="space_id" value="<?php echo $entity->id; ?>">

                <div class="form-group">
                    <label><?php \MapasCulturais\i::_e('Data:'); ?></label>
                    <input type="date" name="date" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><?php \MapasCulturais\i::_e('Horário de início:'); ?></label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php \MapasCulturais\i::_e('Horário de término:'); ?></label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php \MapasCulturais\i::_e('Número de pessoas:'); ?></label>
                    <input type="number" name="num_people" class="form-control"
                           min="1"
                           <?php if ($maxCapacity): ?>max="<?php echo $maxCapacity; ?>"<?php endif; ?>>
                    <?php if ($maxCapacity): ?>
                        <small><?php printf(\MapasCulturais\i::__('Máximo: %d pessoas'), $maxCapacity); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label><?php \MapasCulturais\i::_e('Finalidade do uso:'); ?> *</label>
                    <textarea name="purpose" class="form-control" rows="3" required
                              placeholder="<?php \MapasCulturais\i::_e('Descreva o objetivo da reserva...'); ?>"></textarea>
                </div>

                <div class="form-group">
                    <label><?php \MapasCulturais\i::_e('Requisitos especiais:'); ?></label>
                    <textarea name="special_requirements" class="form-control" rows="2"
                              placeholder="<?php \MapasCulturais\i::_e('Equipamentos, acessibilidade, etc.'); ?>"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-default cancel-modal">
                        <?php \MapasCulturais\i::_e('Cancelar'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?php \MapasCulturais\i::_e('Solicitar Reserva'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
