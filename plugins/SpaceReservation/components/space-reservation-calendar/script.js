app.component('space-reservation-calendar', {
    template: $TEMPLATES['space-reservation-calendar'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    setup() {
        const messages = useMessages();
        return { messages };
    },

    data() {
        const now = new Date();
        const config = $MAPAS.config.spaceReservationCalendar || {};

        return {
            // Calendar state
            currentYear: now.getFullYear(),
            currentMonth: now.getMonth(),
            reservations: [],
            loading: false,

            // Auth state from server
            isLoggedIn: config.isLoggedIn || false,
            canRequest: config.canRequest || false,
            baseURL: config.baseURL || '',

            // Reservation form
            selectedDate: null,
            showForm: false,
            formData: {
                start_time: '08:00',
                end_time: '18:00',
                purpose: '',
                num_people: null,
                special_requirements: '',
                non_profit_declaration: false,
                terms_declaration: false,
            },
            submitting: false,
            formErrors: {},

            // Day detail
            selectedDayReservations: [],
        };
    },

    computed: {
        monthNames() {
            return [
                'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
            ];
        },

        weekDayNames() {
            return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        },

        currentMonthLabel() {
            return `${this.monthNames[this.currentMonth]} ${this.currentYear}`;
        },

        calendarDays() {
            const year = this.currentYear;
            const month = this.currentMonth;
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startWeekDay = firstDay.getDay();
            const totalDays = lastDay.getDate();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const days = [];

            // Empty cells before first day
            for (let i = 0; i < startWeekDay; i++) {
                days.push({ empty: true });
            }

            // Actual days
            for (let d = 1; d <= totalDays; d++) {
                const date = new Date(year, month, d);
                const dateStr = this.formatDateISO(date);
                const isPast = date < today;
                const dayReservations = this.getReservationsForDate(dateStr);

                days.push({
                    empty: false,
                    day: d,
                    date: dateStr,
                    isPast: isPast,
                    isToday: date.getTime() === today.getTime(),
                    reservations: dayReservations,
                    status: this.getDayStatus(dayReservations, isPast, dateStr),
                });
            }

            return days;
        },

        maxCapacity() {
            return this.entity.reservation_max_capacity || 0;
        },

        canNavigatePrev() {
            const now = new Date();
            return this.currentYear > now.getFullYear() ||
                   (this.currentYear === now.getFullYear() && this.currentMonth > now.getMonth());
        },

        selectedDateFormatted() {
            if (!this.selectedDate) return '';
            const parts = this.selectedDate.split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        },
    },

    mounted() {
        this.loadAvailability();
    },

    methods: {
        // --- Navigation ---
        prevMonth() {
            if (!this.canNavigatePrev) return;
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.loadAvailability();
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.loadAvailability();
        },

        // --- Data loading ---
        async loadAvailability() {
            if (!this.entity.id) return;

            this.loading = true;
            const startDate = this.formatDateISO(new Date(this.currentYear, this.currentMonth, 1));
            const endDate = this.formatDateISO(new Date(this.currentYear, this.currentMonth + 1, 0));

            try {
                const url = `${this.baseURL}api/space-reservation/availability?space_id=${this.entity.id}&start=${startDate}&end=${endDate}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.error) {
                    console.error('Error loading availability:', data.error);
                    this.reservations = [];
                } else {
                    this.reservations = data.reservations || [];
                }
            } catch (e) {
                console.error('Error loading availability:', e);
                this.reservations = [];
            } finally {
                this.loading = false;
            }
        },

        // --- Day interaction ---
        selectDay(day) {
            if (day.empty || day.isPast) return;

            this.selectedDate = day.date;
            this.selectedDayReservations = day.reservations;

            if (this.canRequest) {
                this.resetForm();
                // Trigger hidden modal button
                this.$nextTick(() => {
                    if (this.$refs.modalTrigger) {
                        this.$refs.modalTrigger.click();
                    }
                });
            }
        },

        resetForm() {
            this.formData = {
                start_time: '08:00',
                end_time: '18:00',
                purpose: '',
                num_people: null,
                special_requirements: '',
                non_profit_declaration: false,
                terms_declaration: false,
            };
            this.formErrors = {};
        },

        closeForm() {
            this.showForm = false;
            this.selectedDate = null;
            this.selectedDayReservations = [];
        },

        // --- Form submission ---
        async submitReservation(modal) {
            this.formErrors = {};

            // Client-side validation
            if (!this.formData.purpose.trim()) {
                this.formErrors.purpose = 'A finalidade é obrigatória';
                return;
            }

            if (this.formData.start_time >= this.formData.end_time) {
                this.formErrors.start_time = 'O horário de início deve ser anterior ao término';
                return;
            }

            if (this.maxCapacity > 0 && this.formData.num_people > this.maxCapacity) {
                this.formErrors.num_people = `Máximo de ${this.maxCapacity} pessoas`;
                return;
            }

            if (!this.formData.non_profit_declaration) {
                this.formErrors.non_profit_declaration = 'Você deve aceitar a declaração de evento sem fins lucrativos.';
                return;
            }

            if (!this.formData.terms_declaration) {
                this.formErrors.terms_declaration = 'Você deve aceitar a declaração de veracidade e os Termos de Reserva.';
                return;
            }

            this.submitting = true;

            const startDateTime = `${this.selectedDate}T${this.formData.start_time}:00`;
            const endDateTime = `${this.selectedDate}T${this.formData.end_time}:00`;

            const payload = {
                space_id: this.entity.id,
                start_time: startDateTime,
                end_time: endDateTime,
                purpose: this.formData.purpose,
                num_people: this.formData.num_people || null,
                special_requirements: this.formData.special_requirements || '',
                non_profit_declaration: this.formData.non_profit_declaration,
                terms_declaration: this.formData.terms_declaration,
            };

            try {
                const url = `${this.baseURL}api/space-reservation/`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    this.messages.error(data.data || data.error || 'Erro ao solicitar reserva');
                    return;
                }

                this.messages.success('Reserva solicitada com sucesso! Aguarde a aprovação do gestor do espaço.');
                
                if (modal && modal.close) {
                    modal.close();
                }
                this.closeForm();
                this.loadAvailability();
            } catch (e) {
                console.error('Error submitting reservation:', e);
                this.messages.error('Erro ao solicitar reserva. Tente novamente.');
            } finally {
                this.submitting = false;
            }
        },

        // --- Helpers ---
        getReservationsForDate(dateStr) {
            const dayStart = new Date(`${dateStr}T00:00:00`);
            const dayEndExclusive = new Date(`${dateStr}T00:00:00`);
            dayEndExclusive.setDate(dayEndExclusive.getDate() + 1);

            return this.reservations.filter(r => {
                const reservationStart = new Date(r.start_time);
                const reservationEnd = new Date(r.end_time);
                return reservationStart < dayEndExclusive && reservationEnd > dayStart;
            });
        },

        getDayStatus(reservations, isPast, dateStr) {
            if (isPast) return 'past';
            if (reservations.length === 0) return 'available';
            // If any reservation covers a full day (8+ hours), mark as occupied
            const totalHours = reservations.reduce((sum, r) => {
                const dayStart = new Date(`${dateStr}T00:00:00`);
                const dayEndExclusive = new Date(`${dateStr}T00:00:00`);
                dayEndExclusive.setDate(dayEndExclusive.getDate() + 1);
                const start = new Date(r.start_time);
                const end = new Date(r.end_time);
                const overlapStart = start > dayStart ? start : dayStart;
                const overlapEnd = end < dayEndExclusive ? end : dayEndExclusive;
                const overlapMs = Math.max(0, overlapEnd - overlapStart);

                return sum + overlapMs / 3600000;
            }, 0);
            if (totalHours >= 8) return 'occupied';
            return 'partial';
        },

        formatDateISO(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        formatTime(isoString) {
            const d = new Date(isoString);
            return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        },

        getDayClasses(day) {
            if (day.empty) return 'calendar-day--empty';
            const classes = ['calendar-day'];
            if (day.isPast) classes.push('calendar-day--past');
            if (day.isToday) classes.push('calendar-day--today');
            if (day.status === 'available') classes.push('calendar-day--available');
            if (day.status === 'occupied') classes.push('calendar-day--occupied');
            if (day.status === 'partial') classes.push('calendar-day--partial');
            if (day.date === this.selectedDate) classes.push('calendar-day--selected');
            return classes.join(' ');
        },
    },
});
