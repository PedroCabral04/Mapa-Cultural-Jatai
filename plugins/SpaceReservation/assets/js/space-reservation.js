/**
 * Space Reservation Plugin - Calendar and Form Handling
 */

(function($) {
    'use strict';

    // Namespace
    window.SpaceReservation = window.SpaceReservation || {};

    // Calendar Component
    SpaceReservation.Calendar = {
        currentDate: new Date(),
        selectedDate: null,
        reservations: [],
        spaceId: null,
        canRequest: false,

        init: function() {
            const container = document.getElementById('space-reservation-calendar');
            if (!container) return;

            this.spaceId = container.dataset.spaceId;
            this.canRequest = container.dataset.canRequest === 'true';

            this.bindEvents();
            this.loadReservations();
        },

        bindEvents: function() {
            const self = this;

            // Navegação do calendário
            $('.prev-month').on('click', function() {
                self.currentDate.setMonth(self.currentDate.getMonth() - 1);
                self.renderCalendar();
                self.loadReservations();
            });

            $('.next-month').on('click', function() {
                self.currentDate.setMonth(self.currentDate.getMonth() + 1);
                self.renderCalendar();
                self.loadReservations();
            });

            // Modal events
            $('.close-modal, .cancel-modal').on('click', function() {
                $('#reservation-modal').hide();
            });

            // Form submission
            $('#reservation-form').on('submit', function(e) {
                e.preventDefault();
                self.submitReservation();
            });

            // Close modal on outside click
            $('#reservation-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
        },

        renderCalendar: function() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            // Update header
            const monthNames = [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
            $('.current-month').text(`${monthNames[month]} ${year}`);

            // Clear days
            const daysContainer = $('.calendar-days');
            daysContainer.empty();

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            // Previous month days
            for (let i = firstDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                daysContainer.append(`
                    <div class="calendar-day other-month disabled" data-date="${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}">
                        ${day}
                    </div>
                `);
            }

            // Current month days
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                let classes = ['calendar-day'];

                // Check if past
                if (date < today) {
                    classes.push('past');
                } else {
                    classes.push('available');
                }

                const dayEl = $(`
                    <div class="${classes.join(' ')}" data-date="${dateStr}">
                        ${day}
                    </div>
                `);

                if (date >= today && this.canRequest) {
                    dayEl.on('click', () => this.openModal(dateStr));
                }

                daysContainer.append(dayEl);
            }

            // Next month days to fill grid
            const totalCells = firstDay + daysInMonth;
            const remainingCells = 42 - totalCells; // 6 rows * 7 days

            for (let day = 1; day <= remainingCells; day++) {
                daysContainer.append(`
                    <div class="calendar-day other-month disabled" data-date="${year}-${String(month + 2).padStart(2, '0')}-${String(day).padStart(2, '0')}">
                        ${day}
                    </div>
                `);
            }

            this.updateCalendarStatus();
        },

        loadReservations: function() {
            const self = this;
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            const startDate = `${year}-${String(month + 1).padStart(2, '0')}-01`;
            const endDate = `${year}-${String(month + 2).padStart(2, '0')}-01`;

            $.ajax({
                url: MapasCulturais.baseURL + 'api/space-reservation/availability',
                data: {
                    space_id: this.spaceId,
                    start: startDate,
                    end: endDate
                },
                success: function(response) {
                    self.reservations = response.reservations || [];
                    self.updateCalendarStatus();
                },
                error: function() {
                    console.error('Failed to load reservations');
                }
            });
        },

        updateCalendarStatus: function() {
            const self = this;

            // Group reservations by date
            const reservationsByDate = {};
            this.reservations.forEach(function(res) {
                const date = res.start_time.split('T')[0];
                if (!reservationsByDate[date]) {
                    reservationsByDate[date] = [];
                }
                reservationsByDate[date].push(res);
            });

            // Update day classes
            $('.calendar-day').each(function() {
                const date = $(this).data('date');
                const dayReservations = reservationsByDate[date] || [];

                if (dayReservations.length > 0) {
                    $(this).removeClass('available').addClass('occupied');

                    // Check if fully occupied (simplified check)
                    const totalHours = dayReservations.reduce(function(sum, res) {
                        const start = new Date(res.start_time);
                        const end = new Date(res.end_time);
                        return sum + (end - start) / (1000 * 60 * 60);
                    }, 0);

                    if (totalHours < 8) {
                        $(this).removeClass('occupied').addClass('partial');
                    }
                }
            });
        },

        openModal: function(dateStr) {
            this.selectedDate = dateStr;
            $('#reservation-form')[0].reset();
            $('input[name="date"]').val(dateStr);
            $('#reservation-modal').show();
        },

        submitReservation: function() {
            const self = this;

            // Parse date and time
            const date = $('input[name="date"]').val();
            const startTime = $('input[name="start_time"]').val();
            const endTime = $('input[name="end_time"]').val();
            const nonProfitDeclaration = $('input[name="non_profit_declaration"]').is(':checked');
            const termsDeclaration = $('input[name="terms_declaration"]').is(':checked');

            if (!nonProfitDeclaration) {
                self.showMessage('Você deve aceitar a declaração de evento sem fins lucrativos.', 'error');
                return;
            }

            if (!termsDeclaration) {
                self.showMessage('Você deve aceitar a declaração de veracidade e os Termos de Reserva.', 'error');
                return;
            }

            const data = {
                space_id: this.spaceId,
                start_time: `${date}T${startTime}`,
                end_time: `${date}T${endTime}`,
                purpose: $('textarea[name="purpose"]').val(),
                num_people: $('input[name="num_people"]').val(),
                special_requirements: $('textarea[name="special_requirements"]').val(),
                non_profit_declaration: nonProfitDeclaration,
                terms_declaration: termsDeclaration
            };

            $.ajax({
                url: MapasCulturais.baseURL + 'api/space-reservation',
                method: 'POST',
                data: data,
                success: function(response) {
                    $('#reservation-modal').hide();
                    self.showMessage('Reserva solicitada com sucesso! Aguarde aprovação do gestor.', 'success');
                    self.loadReservations();
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.error || 'Erro ao solicitar reserva. Tente novamente.';
                    self.showMessage(error, 'error');
                }
            });
        },

        showMessage: function(message, type) {
            const alertClass = type === 'success' ? 'alert success' : 'alert error';
            const html = `<div class="${alertClass}">${message}</div>`;

            $('.space-reservation-container').prepend(html);

            setTimeout(function() {
                $('.alert').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SpaceReservation.Calendar.init();
    });

})(jQuery);
