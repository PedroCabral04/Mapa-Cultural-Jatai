<?php
/**
 * Template do componente space-reservation-management
 */

use MapasCulturais\i;

$this->import('mc-loading');
?>

<div class="space-reservation-management">
    <div class="space-reservation-management__header">
        <h2><?php i::_e('Gestão de Reservas'); ?></h2>

        <label class="space-reservation-management__filter-label">
            <?php i::_e('Status'); ?>
            <select v-model="statusFilter" @change="loadReservations()" class="space-reservation-management__filter">
                <option value="all"><?php i::_e('Todos'); ?></option>
                <option value="pending"><?php i::_e('Pendentes'); ?></option>
                <option value="approved"><?php i::_e('Aprovadas'); ?></option>
                <option value="rejected"><?php i::_e('Rejeitadas'); ?></option>
                <option value="cancelled"><?php i::_e('Canceladas'); ?></option>
            </select>
        </label>

        <label v-if="!entity || !entity.id" class="space-reservation-management__filter-label">
            <?php i::_e('ID do espaço (opcional)'); ?>
            <input type="number" min="1" v-model="spaceIdFilter" @change="loadReservations()" class="space-reservation-management__filter">
        </label>
    </div>

    <mc-loading :condition="loading"></mc-loading>

    <div v-if="!loading && reservations.length === 0" class="space-reservation-management__empty">
        <?php i::_e('Nenhuma reserva encontrada para o filtro selecionado.'); ?>
    </div>

    <div v-if="!loading && reservations.length > 0" class="space-reservation-management__list">
        <article v-for="reservation in reservations" :key="reservation.id" class="space-reservation-management__item">
            <div class="space-reservation-management__item-main">
                <p class="space-reservation-management__item-date">
                    <strong>{{ formatDate(reservation.start_time) }}</strong>
                    {{ formatTime(reservation.start_time) }} - {{ formatTime(reservation.end_time) }}
                </p>
                <p class="space-reservation-management__item-requester" v-if="reservation.requester">
                    <strong><?php i::_e('Solicitante:'); ?></strong> {{ reservation.requester.name }}
                </p>
                <p class="space-reservation-management__item-space" v-if="reservation.space">
                    <strong><?php i::_e('Espaço:'); ?></strong> {{ reservation.space.name }}
                </p>
                <p class="space-reservation-management__item-purpose" v-if="reservation.purpose">
                    <strong><?php i::_e('Finalidade:'); ?></strong> {{ reservation.purpose }}
                </p>
                <p class="space-reservation-management__item-people" v-if="reservation.num_people">
                    <strong><?php i::_e('Pessoas:'); ?></strong> {{ reservation.num_people }}
                </p>
                <p class="space-reservation-management__item-reason" v-if="reservation.rejection_reason">
                    <strong><?php i::_e('Motivo da rejeição:'); ?></strong> {{ reservation.rejection_reason }}
                </p>
            </div>

            <div class="space-reservation-management__item-side">
                <span :class="['space-reservation-management__status', 'space-reservation-management__status--' + reservation.status]">
                    {{ statusLabel(reservation.status) }}
                </span>

                <div v-if="reservation.status === 'pending'" class="space-reservation-management__actions">
                    <button class="button button--sm button--primary"
                            :disabled="processingId === reservation.id"
                            @click="approveReservation(reservation)">
                        <?php i::_e('Aprovar'); ?>
                    </button>
                    <button class="button button--sm button--secondary"
                            :disabled="processingId === reservation.id"
                            @click="rejectReservation(reservation)">
                        <?php i::_e('Rejeitar'); ?>
                    </button>
                </div>
            </div>
        </article>
    </div>
</div>
