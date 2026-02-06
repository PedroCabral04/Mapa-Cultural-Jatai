<?php

namespace SpaceReservation;

use MapasCulturais\App;
use MapasCulturais\i;
use SpaceReservation\Entities\SpaceReservation;

class Controller extends \MapasCulturais\Controllers\EntityController
{
    /**
     * Nome da entidade
     */
    public static $entityName = 'SpaceReservation\Entities\SpaceReservation';

    /**
     * Verifica disponibilidade de horários para um espaço
     *
     * GET /api/space-reservation/availability?space_id=123&start=2026-02-01&end=2026-02-28
     */
    public function GET_availability()
    {
        $app = App::i();

        $spaceId = $this->data['space_id'] ?? null;
        $start = $this->data['start'] ?? null;
        $end = $this->data['end'] ?? null;

        if (!$spaceId) {
            $this->errorJson(i::__('Parâmetro space_id é obrigatório'), 400);
            return;
        }

        $space = $app->repo('Space')->find($spaceId);
        if (!$space) {
            $this->errorJson(i::__('Espaço não encontrado'), 404);
            return;
        }

        // Define período padrão se não informado (próximo mês)
        if (!$start) {
            $start = date('Y-m-d');
        }
        if (!$end) {
            $end = date('Y-m-d', strtotime('+1 month'));
        }

        try {
            $startDate = new \DateTime($start);
            $startDate->setTime(0, 0, 0);

            $endDate = new \DateTime($end);
            $endDate->setTime(0, 0, 0);

            $endExclusive = clone $endDate;
            $endExclusive->modify('+1 day');
        } catch (\Exception $e) {
            $this->errorJson(i::__('Parâmetros de data inválidos'), 400);
            return;
        }

        // Busca reservas aprovadas no período
        $qb = $app->em->createQueryBuilder();
        $qb->select('r')
            ->from('SpaceReservation\Entities\SpaceReservation', 'r')
            ->where('r.space = :space')
            ->andWhere('r.status = :status')
            ->andWhere('r.startTime < :end_exclusive')
            ->andWhere('r.endTime > :start')
            ->setParameter('space', $space)
            ->setParameter('status', 'approved')
            ->setParameter('start', $startDate)
            ->setParameter('end_exclusive', $endExclusive)
            ->orderBy('r.startTime', 'ASC');

        $reservations = $qb->getQuery()->getResult();

        $result = [];
        foreach ($reservations as $r) {
            $result[] = [
                'id' => $r->id,
                'start_time' => $r->getStartTime()->format('c'),
                'end_time' => $r->getEndTime()->format('c'),
                'status' => $r->getStatus(),
            ];
        }

        $this->json([
            'space_id' => (int) $spaceId,
            'period' => [
                'start' => $start,
                'end' => $end,
            ],
            'reservations' => $result,
        ]);
    }

    public function API_availability()
    {
        $this->GET_availability();
    }

    /**
     * Lista reservas do usuário logado
     *
     * GET /api/space-reservation
     */
    public function GET_index()
    {
        $app = App::i();
        $user = $app->user;

        if ($user->is('guest')) {
            $this->errorJson(i::__('Autenticação necessária'), 401);
            return;
        }

        // Pega o agente padrão do usuário
        $agent = $user->profile;

        $qb = $app->em->createQueryBuilder();
        $qb->select('r')
            ->from('SpaceReservation\Entities\SpaceReservation', 'r')
            ->where('r.requester = :requester')
            ->setParameter('requester', $agent)
            ->orderBy('r.startTime', 'DESC');

        // Filtro por status
        if (isset($this->data['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $this->data['status']);
        }

        $reservations = $qb->getQuery()->getResult();

        $result = [];
        foreach ($reservations as $r) {
            $result[] = $this->serializeReservation($r);
        }

        $this->json($result);
    }

    public function API_index()
    {
        if (strtoupper(App::i()->request->getMethod()) === 'POST') {
            $this->POST_index($this->data);
            return;
        }

        $this->GET_index();
    }

    /**
     * Lista reservas dos espaços gerenciados pelo usuário
     *
     * GET /api/space-reservation/manage
     */
    public function GET_manage()
    {
        $app = App::i();
        $user = $app->user;

        if ($user->is('guest')) {
            $this->errorJson(i::__('Autenticação necessária'), 401);
            return;
        }

        $qb = $app->em->createQueryBuilder();
        $qb->select('r')
            ->from('SpaceReservation\Entities\SpaceReservation', 'r')
            ->orderBy('r.createdAt', 'DESC');

        $spaceId = $this->data['space_id'] ?? null;
        $isAdmin = $user->is('admin');

        if ($spaceId) {
            $space = $app->repo('Space')->find($spaceId);

            if (!$space) {
                $this->errorJson(i::__('Espaço não encontrado'), 404);
                return;
            }

            if (!$isAdmin && !$space->canUser('@control', $user)) {
                $this->errorJson(i::__('Você não tem permissão para gerenciar reservas deste espaço'), 403);
                return;
            }

            $qb->where('r.space = :space')
               ->setParameter('space', $space);
        } else {
            if ($isAdmin) {
                $qb->where('1 = 1');
            } else {
            $spaces = $app->repo('Space')->findBy(['owner' => $user->profile]);

            if (empty($spaces)) {
                $this->json([]);
                return;
            }

            $qb->where('r.space IN (:spaces)')
               ->setParameter('spaces', $spaces);
            }
        }

        // Filtro por status
        if (isset($this->data['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $this->data['status']);
        }

        $reservations = $qb->getQuery()->getResult();

        $result = [];
        foreach ($reservations as $r) {
            $result[] = $this->serializeReservation($r, true);
        }

        $this->json($result);
    }

    public function API_manage()
    {
        $this->GET_manage();
    }

    /**
     * Cria nova reserva
     *
     * POST /api/space-reservation
     */
    public function POST_index($data = null)
    {
        $app = App::i();
        $user = $app->user;

        if ($user->is('guest')) {
            $this->errorJson(i::__('Autenticação necessária'), 401);
            return;
        }

        // Verifica se usuário tem agente verificado
        $agent = $user->profile;
        if (!$agent || $agent->status < 0) {
            $this->errorJson(i::__('Você precisa ter um agente verificado para solicitar reservas'), 403);
            return;
        }

        if (is_null($data)) {
            $data = $this->data;
        }

        // Valida campos obrigatórios
        if (empty($data['space_id'])) {
            $this->errorJson(i::__('Espaço é obrigatório'), 400);
            return;
        }

        if (empty($data['start_time']) || empty($data['end_time'])) {
            $this->errorJson(i::__('Horário de início e término são obrigatórios'), 400);
            return;
        }

        $space = $app->repo('Space')->find($data['space_id']);
        if (!$space) {
            $this->errorJson(i::__('Espaço não encontrado'), 404);
            return;
        }

        // Cria a reserva
        try {
            $startTime = new \DateTime($data['start_time']);
            $endTime = new \DateTime($data['end_time']);

            $reservation = new SpaceReservation();
            $reservation->setSpace($space);
            $reservation->setRequester($agent);
            $reservation->setStartTime($startTime);
            $reservation->setEndTime($endTime);
            $reservation->setPurpose($data['purpose'] ?? '');
            $reservation->setNumPeople($data['num_people'] ?? null);
            $reservation->setSpecialRequirements($data['special_requirements'] ?? '');

            $reservation->save(true);
        } catch (\Exception $e) {
            $message = trim((string) $e->getMessage());
            $this->errorJson($message !== '' ? $message : i::__('Erro ao criar reserva'), 422);
            return;
        }

        $this->json($this->serializeReservation($reservation), 201);
    }

    /**
     * Aprova uma reserva
     *
     * PATCH /api/space-reservation/:id/approve
     */
    public function PATCH_approve()
    {
        $app = App::i();
        $id = $this->data['id'] ?? null;

        if (!$id) {
            $this->errorJson(i::__('ID da reserva é obrigatório'), 400);
            return;
        }

        $reservation = $app->em->getRepository('SpaceReservation\Entities\SpaceReservation')->find($id);
        if (!$reservation) {
            $this->errorJson(i::__('Reserva não encontrada'), 404);
            return;
        }

        if (!$reservation->canUser('approve', $app->user)) {
            $this->errorJson(i::__('Você não tem permissão para aprovar esta reserva'), 403);
            return;
        }

        try {
            $reservation->approve();
        } catch (\Exception $e) {
            $message = trim((string) $e->getMessage());
            $this->errorJson($message !== '' ? $message : i::__('Erro ao aprovar reserva'), 422);
            return;
        }

        $this->json($this->serializeReservation($reservation, true));
    }

    public function API_approve()
    {
        $this->PATCH_approve();
    }

    /**
     * Rejeita uma reserva
     *
     * PATCH /api/space-reservation/:id/reject
     */
    public function PATCH_reject()
    {
        $app = App::i();
        $id = $this->data['id'] ?? null;

        if (!$id) {
            $this->errorJson(i::__('ID da reserva é obrigatório'), 400);
            return;
        }

        $reservation = $app->em->getRepository('SpaceReservation\Entities\SpaceReservation')->find($id);
        if (!$reservation) {
            $this->errorJson(i::__('Reserva não encontrada'), 404);
            return;
        }

        if (!$reservation->canUser('reject', $app->user)) {
            $this->errorJson(i::__('Você não tem permissão para rejeitar esta reserva'), 403);
            return;
        }

        $reason = $this->data['reason'] ?? null;

        try {
            $reservation->reject($reason);
        } catch (\Exception $e) {
            $message = trim((string) $e->getMessage());
            $this->errorJson($message !== '' ? $message : i::__('Erro ao rejeitar reserva'), 422);
            return;
        }

        $this->json($this->serializeReservation($reservation, true));
    }

    public function API_reject()
    {
        $this->PATCH_reject();
    }

    /**
     * Cancela uma reserva
     *
     * PATCH /api/space-reservation/:id/cancel
     */
    public function PATCH_cancel()
    {
        $app = App::i();
        $id = $this->data['id'] ?? null;

        if (!$id) {
            $this->errorJson(i::__('ID da reserva é obrigatório'), 400);
            return;
        }

        $reservation = $app->em->getRepository('SpaceReservation\Entities\SpaceReservation')->find($id);
        if (!$reservation) {
            $this->errorJson(i::__('Reserva não encontrada'), 404);
            return;
        }

        if (!$reservation->canUser('cancel', $app->user)) {
            $this->errorJson(i::__('Você não tem permissão para cancelar esta reserva'), 403);
            return;
        }

        try {
            $reservation->cancel();
        } catch (\Exception $e) {
            $message = trim((string) $e->getMessage());
            $this->errorJson($message !== '' ? $message : i::__('Erro ao cancelar reserva'), 422);
            return;
        }

        $this->json($this->serializeReservation($reservation));
    }

    public function API_cancel()
    {
        $this->PATCH_cancel();
    }

    /**
     * Serializa uma reserva para JSON
     */
    protected function serializeReservation(SpaceReservation $reservation, $includeRequester = false)
    {
        $data = [
            'id' => $reservation->id,
            'space' => [
                'id' => $reservation->getSpace()->id,
                'name' => $reservation->getSpace()->name,
                'singleUrl' => $reservation->getSpace()->getSingleUrl(),
            ],
            'start_time' => $reservation->getStartTime()->format('c'),
            'end_time' => $reservation->getEndTime()->format('c'),
            'status' => $reservation->getStatus(),
            'purpose' => $reservation->getPurpose(),
            'num_people' => $reservation->getNumPeople(),
            'special_requirements' => $reservation->getSpecialRequirements(),
            'rejection_reason' => $reservation->getRejectionReason(),
            'created_at' => $reservation->getCreatedAt()->format('c'),
        ];

        if ($includeRequester) {
            $requester = $reservation->getRequester();
            $data['requester'] = [
                'id' => $requester->id,
                'name' => $requester->name,
                'singleUrl' => $requester->getSingleUrl(),
            ];
        }

        return $data;
    }

}
