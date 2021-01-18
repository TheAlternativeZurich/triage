<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Invoice;

use App\Entity\Delegation;

class Invoice
{
    /** @var int */
    private $guestCount;
    /** @var int */
    private $guestSurcharge;
    /** @var int */
    private $totalGuestSurcharge;

    /** @var int */
    private $singleRoomCount;
    /** @var int */
    private $singleRoomSurcharge;
    /** @var int */
    private $totalSingleRoomSurcharge;

    /** @var int */
    private $total;

    /** @var int */
    private $alreadyPayed;

    /** @var int */
    private $outstandingAmount;

    public static function createFromDelegation(Delegation $delegation, int $guestSurcharge, int $singleRoomSurcharge): Invoice
    {
        $self = new self();
        $self->guestCount = $delegation->getGuestCount();
        $self->guestSurcharge = $guestSurcharge;
        $self->totalGuestSurcharge = $self->guestCount * $self->guestSurcharge;

        $self->singleRoomCount = 0;
        foreach ($delegation->getParticipants() as $participant) {
            $self->singleRoomCount += $participant->getSingleRoom() ? 1 : 0;
        }
        $self->singleRoomSurcharge = $singleRoomSurcharge;
        $self->totalSingleRoomSurcharge = $self->singleRoomCount * $self->singleRoomSurcharge;

        $self->alreadyPayed = $delegation->getAlreadyPayed();

        $self->total = $self->totalGuestSurcharge + $self->totalSingleRoomSurcharge;
        $self->outstandingAmount = $self->total - $self->alreadyPayed;

        return $self;
    }

    public function getGuestCount(): int
    {
        return $this->guestCount;
    }

    public function getGuestSurcharge(): int
    {
        return $this->guestSurcharge;
    }

    public function getTotalGuestSurcharge(): int
    {
        return $this->totalGuestSurcharge;
    }

    public function getSingleRoomCount(): int
    {
        return $this->singleRoomCount;
    }

    public function getSingleRoomSurcharge(): int
    {
        return $this->singleRoomSurcharge;
    }

    public function getTotalSingleRoomSurcharge(): int
    {
        return $this->totalSingleRoomSurcharge;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getAlreadyPayed(): int
    {
        return $this->alreadyPayed;
    }

    public function getOutstandingAmount(): int
    {
        return $this->outstandingAmount;
    }
}
