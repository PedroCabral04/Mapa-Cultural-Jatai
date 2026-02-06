<?php

namespace SpaceReservation\Entities;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\App;

/**
 * @ORM\Table(name="space_reservation")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class SpaceReservation extends \MapasCulturais\Entity
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="space_reservation_id_seq", allocationSize=1, initialValue=1)
     */
    public $id;

    /**
     * @var \MapasCulturais\Entities\Space
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Space")
     * @ORM\JoinColumn(name="space_id", referencedColumnName="id", nullable=false)
     */
    protected $space;

    /**
     * @var \MapasCulturais\Entities\Agent
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Agent")
     * @ORM\JoinColumn(name="requester_id", referencedColumnName="id", nullable=false)
     */
    protected $requester;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_time", type="datetime", nullable=false)
     */
    protected $startTime;

    /**
     * @var \DateTime
     * @ORM\Column(name="end_time", type="datetime", nullable=false)
     */
    protected $endTime;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=20, nullable=false)
     */
    protected $status = 'pending';

    /**
     * @var string
     * @ORM\Column(name="purpose", type="text", nullable=true)
     */
    protected $purpose;

    /**
     * @var integer
     * @ORM\Column(name="num_people", type="integer", nullable=true)
     */
    protected $numPeople;

    /**
     * @var string
     * @ORM\Column(name="special_requirements", type="text", nullable=true)
     */
    protected $specialRequirements;

    /**
     * @var string
     * @ORM\Column(name="rejection_reason", type="text", nullable=true)
     */
    protected $rejectionReason;

    /**
     * @var boolean
     * @ORM\Column(name="non_profit_declaration", type="boolean", nullable=false, options={"default": false})
     */
    protected $nonProfitDeclaration = false;

    /**
     * @var boolean
     * @ORM\Column(name="terms_declaration", type="boolean", nullable=false, options={"default": false})
     */
    protected $termsDeclaration = false;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    // Guarda o status anterior para detectar mudanças
    protected $_oldStatus;

    /**
     * @ORM\PrePersist
     */
    public function prePersist($args = null)
    {
        parent::prePersist($args);
        $this->createdAt = new \DateTime();
        $this->_oldStatus = $this->status;
        $this->validate();
    }

    /**
     * @ORM\PostLoad
     */
    public function postLoad($args = null)
    {
        $this->_oldStatus = $this->status;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate($args = null)
    {
        parent::preUpdate($args);
        $this->updatedAt = new \DateTime();

        if ($args && method_exists($args, 'hasChangedField') && $args->hasChangedField('status')) {
            $this->_oldStatus = $args->getOldValue('status');
        } else {
            $this->_oldStatus = $this->status;
        }
    }

    /**
     * Validações da reserva
     */
    protected function validate()
    {
        $app = App::i();

        // Verifica se o espaço permite reservas
        if (!$this->space->reservation_enabled) {
            throw new \Exception(\MapasCulturais\i::__('Este espaço não aceita reservas no momento.'));
        }

        // Verifica se o horário de fim é depois do início
        if ($this->endTime <= $this->startTime) {
            throw new \Exception(\MapasCulturais\i::__('O horário de término deve ser posterior ao horário de início.'));
        }

        // Verifica antecedência mínima
        $minNoticeDays = $this->space->reservation_min_notice_days ?: 2;
        $minDate = new \DateTime("+{$minNoticeDays} days");
        if ($this->startTime < $minDate) {
            throw new \Exception(vsprintf(\MapasCulturais\i::__('É necessário solicitar com pelo menos %d dias de antecedência.'), [$minNoticeDays]));
        }

        // Verifica antecedência máxima
        $maxAdvanceDays = $this->space->reservation_max_advance_days ?: 90;
        $maxDate = new \DateTime("+{$maxAdvanceDays} days");
        if ($this->startTime > $maxDate) {
            throw new \Exception(vsprintf(\MapasCulturais\i::__('Não é possível reservar com mais de %d dias de antecedência.'), [$maxAdvanceDays]));
        }

        // Verifica capacidade
        if ($this->numPeople && $this->space->reservation_max_capacity) {
            if ($this->numPeople > $this->space->reservation_max_capacity) {
                throw new \Exception(vsprintf(\MapasCulturais\i::__('Este espaço comporta no máximo %d pessoas.'), [$this->space->reservation_max_capacity]));
            }
        }

        if (!$this->nonProfitDeclaration) {
            throw new \Exception(\MapasCulturais\i::__('Você deve aceitar a declaração de evento sem fins lucrativos.'));
        }

        if (!$this->termsDeclaration) {
            throw new \Exception(\MapasCulturais\i::__('Você deve aceitar a declaração de veracidade e os Termos de Reserva.'));
        }

        // Verifica conflito de horário com reservas aprovadas
        $this->checkConflicts();
    }

    /**
     * Verifica conflitos de horário com outras reservas aprovadas
     */
    protected function checkConflicts()
    {
        $app = App::i();
        $maxSimultaneousReservations = $this->getMaxSimultaneousReservations();

        $qb = $app->em->createQueryBuilder();
        $qb->select('r')
            ->from('SpaceReservation\Entities\SpaceReservation', 'r')
            ->where('r.space = :space')
            ->andWhere('r.status = :status')
            ->andWhere(
                $qb->expr()->orX(
                    // Nova reserva começa durante uma existente
                    $qb->expr()->andX(
                        $qb->expr()->lte('r.startTime', ':new_start'),
                        $qb->expr()->gt('r.endTime', ':new_start')
                    ),
                    // Nova reserva termina durante uma existente
                    $qb->expr()->andX(
                        $qb->expr()->lt('r.startTime', ':new_end'),
                        $qb->expr()->gte('r.endTime', ':new_end')
                    ),
                    // Nova reserva engloba uma existente
                    $qb->expr()->andX(
                        $qb->expr()->gte('r.startTime', ':new_start'),
                        $qb->expr()->lte('r.endTime', ':new_end')
                    )
                )
            )
            ->setParameter('space', $this->space)
            ->setParameter('status', 'approved')
            ->setParameter('new_start', $this->startTime)
            ->setParameter('new_end', $this->endTime);

        if ($this->id) {
            $qb->andWhere('r.id != :current_id')
               ->setParameter('current_id', $this->id);
        }

        $conflicts = $qb->getQuery()->getResult();

        if (count($conflicts) >= $maxSimultaneousReservations) {
            throw new \Exception(vsprintf(\MapasCulturais\i::__('Este horário atingiu o limite máximo de %d reservas simultâneas.'), [$maxSimultaneousReservations]));
        }
    }

    /**
     * Obtém o limite de reservas simultâneas para o espaço.
     */
    protected function getMaxSimultaneousReservations()
    {
        if (!$this->space) {
            return 1;
        }

        if (!$this->space->reservation_allow_simultaneous) {
            return 1;
        }

        $maxSimultaneous = (int) ($this->space->reservation_max_simultaneous ?? 1);
        return max(1, $maxSimultaneous);
    }

    /**
     * Aprova a reserva
     */
    public function approve()
    {
        if ($this->status !== 'pending') {
            throw new \Exception(\MapasCulturais\i::__('Apenas reservas pendentes podem ser aprovadas.'));
        }

        // Revalida conflitos no momento da aprovação para evitar dupla reserva
        $this->checkConflicts();

        $this->_oldStatus = $this->status;
        $this->status = 'approved';
        $this->save(true);
    }

    /**
     * Rejeita a reserva
     */
    public function reject($reason = null)
    {
        if ($this->status !== 'pending') {
            throw new \Exception(\MapasCulturais\i::__('Apenas reservas pendentes podem ser rejeitadas.'));
        }

        $this->_oldStatus = $this->status;
        $this->status = 'rejected';
        $this->rejectionReason = $reason;
        $this->save(true);
    }

    /**
     * Cancela a reserva
     */
    public function cancel()
    {
        if ($this->status === 'cancelled') {
            throw new \Exception(\MapasCulturais\i::__('Esta reserva já foi cancelada.'));
        }

        // Não permite cancelar reservas já passadas
        if ($this->endTime < new \DateTime()) {
            throw new \Exception(\MapasCulturais\i::__('Não é possível cancelar reservas já finalizadas.'));
        }

        $this->_oldStatus = $this->status;
        $this->status = 'cancelled';
        $this->save(true);
    }

    /**
     * Verifica se o status mudou para um valor específico
     */
    public function statusChangedTo($status)
    {
        return $this->_oldStatus !== $this->status && $this->status === $status;
    }

    //============================================================= //
    // The following lines ara used by MapasCulturais hook system.
    // Please do not change them.
    // ============================================================ //

    /** @ORM\PostPersist */
    public function postPersist($args = null){ parent::postPersist($args); }

    /** @ORM\PreRemove */
    public function preRemove($args = null){ parent::preRemove($args); }
    /** @ORM\PostRemove */
    public function postRemove($args = null){ parent::postRemove($args); }

    /** @ORM\PostUpdate */
    public function postUpdate($args = null){ parent::postUpdate($args); }

    // Getters
    public function getSpace() { return $this->space; }
    public function getRequester() { return $this->requester; }
    public function getStartTime() { return $this->startTime; }
    public function getEndTime() { return $this->endTime; }
    public function getStatus() { return $this->status; }
    public function getPurpose() { return $this->purpose; }
    public function getNumPeople() { return $this->numPeople; }
    public function getSpecialRequirements() { return $this->specialRequirements; }
    public function getRejectionReason() { return $this->rejectionReason; }
    public function getNonProfitDeclaration() { return $this->nonProfitDeclaration; }
    public function getTermsDeclaration() { return $this->termsDeclaration; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }

    // Setters
    public function setSpace($space) { $this->space = $space; }
    public function setRequester($requester) { $this->requester = $requester; }
    public function setStartTime($startTime) { $this->startTime = $startTime; }
    public function setEndTime($endTime) { $this->endTime = $endTime; }
    public function setPurpose($purpose) { $this->purpose = $purpose; }
    public function setNumPeople($numPeople) { $this->numPeople = $numPeople; }
    public function setSpecialRequirements($requirements) { $this->specialRequirements = $requirements; }
    public function setNonProfitDeclaration($accepted) { $this->nonProfitDeclaration = (bool) $accepted; }
    public function setTermsDeclaration($accepted) { $this->termsDeclaration = (bool) $accepted; }
}
