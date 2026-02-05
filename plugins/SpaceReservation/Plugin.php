<?php

namespace SpaceReservation;

use MapasCulturais\App;
use MapasCulturais\i;

class Plugin extends \MapasCulturais\Plugin
{
    public function _init()
    {
        $app = App::i();

        // Registra assets (CSS e JS)
        $this->registerAssets();

        // Registra hooks de template para adicionar aba de reservas
        $this->registerTemplateHooks();

        // Registra hooks de notificação
        $this->registerNotificationHooks();

        // Registra hooks de permissão
        $this->registerPermissionHooks();
    }

    /**
     * Registra assets CSS e JS
     */
    protected function registerAssets()
    {
        $app = App::i();

        // Registra CSS
        $app->hook('template(space.single):after', function () {
            $app = App::i();
            $app->view->enqueueStyle('app', 'space-reservation-css', 'css/space-reservation.css');
        });

        // Registra JS
        $app->hook('template(space.single):after', function () {
            $app = App::i();
            $app->view->enqueueScript('app', 'space-reservation-js', 'js/space-reservation.js', ['jquery']);
        });
    }

    public function register()
    {
        $app = App::i();

        // Registra controller da API
        $app->registerController('spaceReservation', Controller::class);

        // Registra metadados no Space
        $this->registerMetadata('MapasCulturais\Entities\Space', 'reservation_enabled', [
            'label' => i::__('Permite reservas'),
            'type' => 'boolean',
            'default' => false,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Space', 'reservation_instructions', [
            'label' => i::__('Instruções para reserva'),
            'type' => 'text',
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Space', 'reservation_max_capacity', [
            'label' => i::__('Capacidade máxima'),
            'type' => 'integer',
            'default' => 0,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Space', 'reservation_min_notice_days', [
            'label' => i::__('Dias mínimos de antecedência'),
            'type' => 'integer',
            'default' => 2,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Space', 'reservation_max_advance_days', [
            'label' => i::__('Dias máximos de antecedência'),
            'type' => 'integer',
            'default' => 90,
        ]);
    }

    /**
     * Registra hooks de template
     */
    protected function registerTemplateHooks()
    {
        $app = App::i();

        // Adiciona aba "Reservas" na página do espaço
        $app->hook('template(space.single.tabs):end', function () {
            if ($this->entity->reservation_enabled) {
                $this->part('space-reservation/tab', ['entity' => $this->entity]);
            }
        });

        // Adiciona conteúdo da aba
        $app->hook('template(space.single.tab-content):end', function () {
            if ($this->entity->reservation_enabled) {
                $this->part('space-reservation/tab-content', ['entity' => $this->entity]);
            }
        });

        // Adiciona campos de configuração no formulário do espaço
        $app->hook('template(space.edit.form):end', function () {
            $this->part('space-reservation/space-config', ['entity' => $this->entity]);
        });
    }

    /**
     * Registra hooks de notificação
     */
    protected function registerNotificationHooks()
    {
        $app = App::i();

        // Notifica gestor quando nova reserva é criada
        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).insert:after', function () use ($app) {
            $space = $this->space;
            $requester = $this->requester;

            // Notifica o gestor do espaço
            $space->owner->notify(
                i::__('Nova solicitação de reserva'),
                vsprintf(i::__('O usuário %s solicitou uma reserva para o espaço "%s" no dia %s das %s às %s.'), [
                    $requester->name,
                    $space->name,
                    $this->startTime->format('d/m/Y'),
                    $this->startTime->format('H:i'),
                    $this->endTime->format('H:i')
                ]),
                $space->getSingleUrl()
            );
        });

        // Notifica solicitante quando status muda
        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).update:after', function () use ($app) {
            if ($this->status !== $this->_oldStatus) {
                $space = $this->space;
                $requester = $this->requester;

                $message = '';
                if ($this->status === 'approved') {
                    $message = vsprintf(i::__('Sua reserva para o espaço "%s" no dia %s foi aprovada.'), [
                        $space->name,
                        $this->startTime->format('d/m/Y')
                    ]);
                } elseif ($this->status === 'rejected') {
                    $reason = $this->rejectionReason ? ' Motivo: ' . $this->rejectionReason : '';
                    $message = vsprintf(i::__('Sua reserva para o espaço "%s" no dia %s foi rejeitada.%s'), [
                        $space->name,
                        $this->startTime->format('d/m/Y'),
                        $reason
                    ]);
                }

                if ($message) {
                    $requester->notify(
                        i::__('Status da reserva alterado'),
                        $message,
                        $space->getSingleUrl()
                    );
                }
            }
        });
    }

    /**
     * Registra hooks de permissão
     */
    protected function registerPermissionHooks()
    {
        $app = App::i();

        // Permissões customizadas para reservas
        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).canUser(approve)', function ($user, &$result) {
            $result = $this->space->canUser('@control', $user);
        });

        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).canUser(reject)', function ($user, &$result) {
            $result = $this->space->canUser('@control', $user);
        });

        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).canUser(cancel)', function ($user, &$result) {
            $result = $this->requester->user->id === $user->id && $this->status !== 'cancelled';
        });
    }
}
