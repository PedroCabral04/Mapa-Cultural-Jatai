app.component('space-reservation-management', {
    template: $TEMPLATES['space-reservation-management'],

    props: {
        entity: {
            type: Entity,
            required: false,
        },
    },

    setup() {
        const messages = useMessages();
        return { messages };
    },

    data() {
        const config = $MAPAS.config.spaceReservationManagement || {};

        return {
            loading: false,
            processingId: null,
            reservations: [],
            statusFilter: 'pending',
            spaceIdFilter: '',
            baseURL: config.baseURL || '',
        };
    },

    mounted() {
        this.loadReservations();
    },

    methods: {
        async loadReservations() {
            this.loading = true;

            try {
                const params = new URLSearchParams();

                if (this.entity && this.entity.id) {
                    params.set('space_id', String(this.entity.id));
                } else if (this.spaceIdFilter) {
                    params.set('space_id', String(this.spaceIdFilter));
                }

                if (this.statusFilter !== 'all') {
                    params.set('status', this.statusFilter);
                }

                const response = await fetch(`${this.baseURL}api/space-reservation/manage?${params.toString()}`);
                const data = await response.json();

                if (!response.ok || data.error) {
                    this.messages.error(data.data || data.error || 'Erro ao carregar reservas');
                    this.reservations = [];
                    return;
                }

                this.reservations = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error('Error loading managed reservations:', e);
                this.messages.error('Erro ao carregar reservas');
                this.reservations = [];
            } finally {
                this.loading = false;
            }
        },

        async approveReservation(reservation) {
            this.processingId = reservation.id;

            try {
                const response = await fetch(`${this.baseURL}api/space-reservation/approve`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: reservation.id }),
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    this.messages.error(data.data || data.error || 'Erro ao aprovar reserva');
                    return;
                }

                this.messages.success('Reserva aprovada com sucesso');
                await this.loadReservations();
            } catch (e) {
                console.error('Error approving reservation:', e);
                this.messages.error('Erro ao aprovar reserva');
            } finally {
                this.processingId = null;
            }
        },

        async rejectReservation(reservation) {
            const reason = window.prompt('Informe o motivo da rejeicao (opcional):', '') || '';

            this.processingId = reservation.id;

            try {
                const response = await fetch(`${this.baseURL}api/space-reservation/reject`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: reservation.id, reason }),
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    this.messages.error(data.data || data.error || 'Erro ao rejeitar reserva');
                    return;
                }

                this.messages.success('Reserva rejeitada');
                await this.loadReservations();
            } catch (e) {
                console.error('Error rejecting reservation:', e);
                this.messages.error('Erro ao rejeitar reserva');
            } finally {
                this.processingId = null;
            }
        },

        statusLabel(status) {
            const labels = {
                pending: 'Pendente',
                approved: 'Aprovada',
                rejected: 'Rejeitada',
                cancelled: 'Cancelada',
            };

            return labels[status] || status;
        },

        formatDate(isoString) {
            const d = new Date(isoString);
            return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
        },

        formatTime(isoString) {
            const d = new Date(isoString);
            return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        },
    },
});
