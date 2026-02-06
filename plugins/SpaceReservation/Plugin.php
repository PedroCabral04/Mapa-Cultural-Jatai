<?php

namespace SpaceReservation;

use MapasCulturais\App;
use MapasCulturais\Exceptions\PermissionDenied;
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

        // Registra hooks de permissão
        $this->registerPermissionHooks();

        // Registra opções no painel administrativo
        $this->registerPanelHooks();

        // Registra validações de configuração
        $this->registerValidationHooks();
    }

    /**
     * Registra assets CSS e JS
     * 
     * Nota: Os assets dos componentes Vue (script.js, style.css) são
     * carregados automaticamente pelo sistema de componentes via $this->import()
     */
    protected function registerAssets()
    {
        // Assets são gerenciados automaticamente pelo sistema de componentes BaseV2
    }

    public function register()
    {
        $app = App::i();

        // Registra controller da API (rota canônica e alias para compatibilidade)
        $app->registerController('space-reservation', Controller::class);
        $app->registerController('spaceReservation', Controller::class);

        // Registra metadados no Space
        $this->registerSpaceMetadataFields();
    }

    /**
     * Registra metadados na entidade Space
     */
    protected function registerSpaceMetadataFields()
    {
        // Habilita reservas no espaço
        $this->registerSpaceMetadata('reservation_enabled', [
            'label' => i::__('Permitir reservas neste espaço'),
            'type' => 'checkbox',
            'default' => false,
        ]);

        $this->registerSpaceMetadata('reservation_allow_simultaneous', [
            'label' => i::__('Permitir mais de uma reserva ao mesmo tempo?'),
            'type' => 'checkbox',
            'default' => false,
        ]);

        $this->registerSpaceMetadata('reservation_max_simultaneous', [
            'label' => i::__('Quantidade máxima de reservas simultâneas'),
            'type' => 'integer',
            'default' => 1,
        ]);

        // Instruções para reserva
        $this->registerSpaceMetadata('reservation_instructions', [
            'label' => i::__('Instruções para reserva'),
            'type' => 'text',
        ]);

        // Capacidade máxima
        $this->registerSpaceMetadata('reservation_max_capacity', [
            'label' => i::__('Capacidade máxima'),
            'type' => 'integer',
            'default' => 0,
        ]);

        // Dias mínimos de antecedência
        $this->registerSpaceMetadata('reservation_min_notice_days', [
            'label' => i::__('Dias mínimos de antecedência'),
            'type' => 'integer',
            'default' => 2,
        ]);

        // Dias máximos de antecedência
        $this->registerSpaceMetadata('reservation_max_advance_days', [
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

        // BaseV2 - Página single do espaço - Adiciona aba de reservas
        // O hook template(space.single.tabs):end é chamado dentro de <mc-tabs>
        // A entidade é uma variável Vue reativa, então usamos v-if no template
        // O import do space-reservation-calendar é feito dentro de tab.php
        $app->hook('template(space.single.tabs):end', function () {
            $this->import('mc-tab mc-container');
            $this->part('space-reservation/tab');
        });

        // BaseV2 - Página de edição do espaço - Adiciona aba de configuração de reservas
        // O hook template(space.edit.tabs):end é chamado dentro de <mc-tabs>
        $app->hook('template(space.edit.tabs):end', function () {
            $this->import('mc-tab mc-container mc-card entity-field');
            $this->part('space-reservation/space-config');
        });

        // Página de busca de espaços - adiciona filtro para espaços com reserva habilitada
        $app->hook('template(search.spaces.search-filter-space):end', function () {
            ?>
            <label class="js-space-reservation-filter-reservable">
                <input
                    v-model="pseudoQuery['reservation_enabled']"
                    true-value="1"
                    :false-value="undefined"
                    type="checkbox"
                >
                <?php i::_e('Aceita reservas') ?>
            </label>
            <?php
        });

        // Página de busca de espaços - reposiciona checkbox junto aos demais status
        $app->hook('template(search.spaces.body):end', function () {
            ?>
            <script>
                (function () {
                    function moveReservableFilter() {
                        document.querySelectorAll('.js-space-reservation-filter-reservable').forEach(function (label) {
                            var form = label.closest('form');
                            var statusContainer = form ? form.querySelector('.search-filter__filter-space-status') : null;

                            if (statusContainer && !statusContainer.contains(label)) {
                                statusContainer.appendChild(label);
                            }
                        });
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', moveReservableFilter);
                    } else {
                        moveReservableFilter();
                    }

                    var observer = new MutationObserver(moveReservableFilter);
                    observer.observe(document.body, { childList: true, subtree: true });
                })();
            </script>
            <?php
        });

        // BaseV2 - Modal de criação de espaço
        // O checkbox é adicionado via override do template create-space 
        // (components/create-space/template.php neste plugin)
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

            $ownerUser = $space && $space->owner ? $space->owner->user : null;
            if (!$ownerUser) {
                return;
            }

            $message = vsprintf(i::__('O usuário %s solicitou uma reserva para o espaço "%s" no dia %s das %s às %s. <a href="%s" rel="noopener noreferrer">Abrir espaço</a>'), [
                $requester->name,
                $space->name,
                $this->startTime->format('d/m/Y'),
                $this->startTime->format('H:i'),
                $this->endTime->format('H:i'),
                $space->getSingleUrl(),
            ]);

            $notification = new Notification();
            $notification->user = $ownerUser;
            $notification->message = $message;
            $notification->save(true);
        });

        // Notifica solicitante quando status muda
        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).update:after', function () use ($app) {
            $space = $this->space;
            $requester = $this->requester;

            $message = '';
            if ($this->statusChangedTo('approved')) {
                $message = vsprintf(i::__('Sua reserva para o espaço "%s" no dia %s foi aprovada.'), [
                    $space->name,
                    $this->startTime->format('d/m/Y')
                ]);
            } elseif ($this->statusChangedTo('rejected')) {
                $reason = $this->rejectionReason ? ' Motivo: ' . $this->rejectionReason : '';
                $message = vsprintf(i::__('Sua reserva para o espaço "%s" no dia %s foi rejeitada.%s'), [
                    $space->name,
                    $this->startTime->format('d/m/Y'),
                    $reason
                ]);
            }

            if ($message && $requester && $requester->user) {
                $notification = new Notification();
                $notification->user = $requester->user;
                $notification->message = sprintf('%s <a href="%s" rel="noopener noreferrer">Abrir espaço</a>', $message, $space->getSingleUrl());
                $notification->save(true);
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
            $result = $user->is('admin') || $this->space->canUser('@control', $user);
        });

        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).canUser(reject)', function ($user, &$result) {
            $result = $user->is('admin') || $this->space->canUser('@control', $user);
        });

        $app->hook('entity(SpaceReservation\Entities\SpaceReservation).canUser(cancel)', function ($user, &$result) {
            $result = $this->requester->user->id === $user->id && $this->status !== 'cancelled';
        });
    }

    protected function registerPanelHooks()
    {
        $app = App::i();

        $app->hook('panel.nav', function (&$group) use ($app) {
            $group['admin']['items'][] = [
                'route' => 'panel/space-reservations',
                'icon' => 'event',
                'label' => i::__('Gestão de Reservas'),
                'condition' => function () use ($app) {
                    return $app->user->is('admin');
                },
            ];
        });

        $app->hook('GET(panel.space-reservations)', function () use ($app) {
            /** @var \MapasCulturais\Controllers\Panel $this */
            $this->requireAuthentication();

            if (!$app->user->is('admin')) {
                throw new PermissionDenied($app->user, null, i::__('Gerenciar Reservas'));
            }

            $this->render('space-reservations');
        });
    }

    protected function registerValidationHooks()
    {
        $app = App::i();

        $app->hook('entity(space).save:before', function () {
            if (!$this->reservation_enabled || !$this->reservation_allow_simultaneous) {
                return;
            }

            $maxSimultaneous = (int) ($this->reservation_max_simultaneous ?? 0);
            if ($maxSimultaneous < 2) {
                throw new \Exception(i::__('Quando permitido mais de uma reserva ao mesmo tempo, a quantidade máxima de reservas simultâneas deve ser no mínimo 2.'));
            }
        });
    }
}
