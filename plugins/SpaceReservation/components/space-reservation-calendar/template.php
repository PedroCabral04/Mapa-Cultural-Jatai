<?php
/**
 * Template do componente space-reservation-calendar (BaseV2)
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('mc-modal mc-icon mc-loading');
?>

<div class="space-reservation-calendar">
    <!-- Header com navegação de mês -->
    <div class="calendar__header">
        <button class="calendar__nav-btn" :class="{'calendar__nav-btn--disabled': !canNavigatePrev}" :disabled="!canNavigatePrev" @click="prevMonth()">
            <mc-icon name="arrow-left"></mc-icon>
        </button>
        <h3 class="calendar__month-label">{{ currentMonthLabel }}</h3>
        <button class="calendar__nav-btn" @click="nextMonth()">
            <mc-icon name="arrow-right"></mc-icon>
        </button>
    </div>

    <!-- Indicador de carregamento -->
    <mc-loading :condition="loading"></mc-loading>

    <!-- Grade do calendário -->
    <div v-if="!loading" class="calendar__grid">
        <!-- Cabeçalho dos dias da semana -->
        <div class="calendar__weekdays">
            <span v-for="dayName in weekDayNames" :key="dayName" class="calendar__weekday">{{ dayName }}</span>
        </div>

        <!-- Dias do mês -->
        <div class="calendar__days">
            <div v-for="(day, index) in calendarDays" :key="index"
                 :class="getDayClasses(day)"
                 @click="selectDay(day)">
                <span v-if="!day.empty" class="calendar__day-number">{{ day.day }}</span>
                <span v-if="!day.empty && day.reservations && day.reservations.length > 0" class="calendar__day-badge">
                    {{ day.reservations.length }}
                </span>
            </div>
        </div>
    </div>

    <!-- Legenda -->
    <div class="calendar__legend">
        <span class="calendar__legend-item">
            <span class="calendar__legend-color calendar__legend-color--available"></span>
            <?php i::_e('Disponível'); ?>
        </span>
        <span class="calendar__legend-item">
            <span class="calendar__legend-color calendar__legend-color--partial"></span>
            <?php i::_e('Parcialmente ocupado'); ?>
        </span>
        <span class="calendar__legend-item">
            <span class="calendar__legend-color calendar__legend-color--occupied"></span>
            <?php i::_e('Reservado'); ?>
        </span>
    </div>

    <!-- Mensagens de autenticação -->
    <div v-if="!isLoggedIn" class="calendar__alert calendar__alert--info">
        <mc-icon name="info"></mc-icon>
        <span><?php i::_e('Faça login para solicitar uma reserva.'); ?></span>
    </div>
    <div v-else-if="!canRequest" class="calendar__alert calendar__alert--warning">
        <mc-icon name="exclamation"></mc-icon>
        <span><?php i::_e('Você precisa ter um agente verificado para solicitar reservas.'); ?></span>
    </div>
    <div v-else class="calendar__alert calendar__alert--tip">
        <mc-icon name="info"></mc-icon>
        <span><?php i::_e('Clique em um dia disponível para solicitar uma reserva.'); ?></span>
    </div>

    <!-- Modal de solicitação de reserva -->
    <mc-modal v-if="canRequest" title="<?php i::esc_attr_e('Solicitar Reserva'); ?>">
        <template #button="modal">
            <button ref="modalTrigger" style="display: none" @click="modal.open()"></button>
        </template>

        <template #default="modal">
            <div class="reservation-form">
                <div class="reservation-form__date">
                    <strong><?php i::_e('Data:'); ?></strong> {{ selectedDateFormatted }}
                </div>

                <!-- Reservas existentes no dia -->
                <div v-if="selectedDayReservations.length > 0" class="reservation-form__existing">
                    <p class="reservation-form__existing-title"><?php i::_e('Reservas já confirmadas neste dia:'); ?></p>
                    <ul class="reservation-form__existing-list">
                        <li v-for="r in selectedDayReservations" :key="r.id" class="reservation-form__existing-item">
                            <div class="reservation-form__existing-info">
                                <span class="reservation-form__existing-time">{{ formatTime(r.start_time) }} - {{ formatTime(r.end_time) }}</span>
                                <strong class="reservation-form__existing-name" v-if="r.requester_name"> - {{ r.requester_name }}</strong>
                            </div>
                            <div v-if="r.purpose" class="reservation-form__existing-purpose">
                                {{ r.purpose }}
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="reservation-form__fields grid-12">
                    <div class="col-6 sm:col-12">
                        <label class="reservation-form__label"><?php i::_e('Horário de início'); ?> *</label>
                        <input type="time" v-model="formData.start_time" class="reservation-form__input" required>
                        <span v-if="formErrors.start_time" class="reservation-form__error">{{ formErrors.start_time }}</span>
                    </div>

                    <div class="col-6 sm:col-12">
                        <label class="reservation-form__label"><?php i::_e('Horário de término'); ?> *</label>
                        <input type="time" v-model="formData.end_time" class="reservation-form__input" required>
                    </div>

                    <div class="col-6 sm:col-12">
                        <label class="reservation-form__label"><?php i::_e('Número de pessoas'); ?></label>
                        <input type="number" v-model.number="formData.num_people"
                               class="reservation-form__input" min="1"
                               :max="maxCapacity > 0 ? maxCapacity : undefined">
                        <small v-if="maxCapacity > 0" class="reservation-form__hint">
                            <?php i::_e('Máximo:'); ?> {{ maxCapacity }} <?php i::_e('pessoas'); ?>
                        </small>
                        <span v-if="formErrors.num_people" class="reservation-form__error">{{ formErrors.num_people }}</span>
                    </div>

                    <div class="col-12">
                        <label class="reservation-form__label"><?php i::_e('Finalidade do uso'); ?> *</label>
                        <textarea v-model="formData.purpose"
                                  class="reservation-form__textarea" rows="3"
                                  placeholder="<?php i::esc_attr_e('Descreva o objetivo da reserva...'); ?>"
                                  required></textarea>
                        <span v-if="formErrors.purpose" class="reservation-form__error">{{ formErrors.purpose }}</span>
                    </div>

                    <div class="col-12">
                        <label class="reservation-form__label"><?php i::_e('Requisitos especiais'); ?></label>
                        <textarea v-model="formData.special_requirements"
                                  class="reservation-form__textarea" rows="2"
                                  placeholder="<?php i::esc_attr_e('Equipamentos, acessibilidade, etc.'); ?>"></textarea>
                    </div>

                    <div class="col-12 reservation-form__declarations">
                        <label class="reservation-form__checkbox-label">
                            <input type="checkbox" v-model="formData.non_profit_declaration" class="reservation-form__checkbox">
                            <span><?php i::_e('Declaro que o evento solicitado não possui fins lucrativos'); ?></span>
                        </label>
                        <span v-if="formErrors.non_profit_declaration" class="reservation-form__error">{{ formErrors.non_profit_declaration }}</span>

                        <label class="reservation-form__checkbox-label">
                            <input type="checkbox" v-model="formData.terms_declaration" class="reservation-form__checkbox">
                            <span><?php i::_e('Declaro, sob as penas da lei, serem verdadeiras todas as informações prestadas. Li e estou de acordo com os Termos de Reserva.'); ?></span>
                        </label>
                        <span v-if="formErrors.terms_declaration" class="reservation-form__error">{{ formErrors.terms_declaration }}</span>
                    </div>
                </div>
            </div>
        </template>

        <template #actions="modal">
            <button class="button button--text" @click="modal.close(); closeForm();">
                <?php i::_e('Cancelar'); ?>
            </button>
            <button class="button button--primary" :disabled="submitting" @click="submitReservation(modal)">
                <span v-if="submitting"><?php i::_e('Enviando...'); ?></span>
                <span v-else><?php i::_e('Solicitar Reserva'); ?></span>
            </button>
        </template>
    </mc-modal>
</div>
