<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Delegation;
use App\Service\Interfaces\InvoiceServiceInterface;
use App\Service\Invoice\Invoice;

class InvoiceService implements InvoiceServiceInterface
{
    /** @var int */
    private $guestSurcharge;
    /** @var int */
    private $singleRoomSurcharge;

    /**
     * InvoiceService constructor.
     */
    public function __construct(int $guestSurcharge, int $singleRoomSurcharge)
    {
        $this->guestSurcharge = $guestSurcharge;
        $this->singleRoomSurcharge = $singleRoomSurcharge;
    }

    public function getInvoice(Delegation $delegation): Invoice
    {
        return Invoice::createFromDelegation($delegation, $this->guestSurcharge, $this->singleRoomSurcharge);
    }

    /**
     * @param Delegation[] $delegations
     *
     * @return Invoice[] indexed by delegation id
     */
    public function getInvoiceByDelegation(array $delegations): array
    {
        $invoices = [];
        foreach ($delegations as $delegation) {
            $invoice = $this->getInvoice($delegation);
            $invoices[$delegation->getId()] = $invoice;
        }

        return $invoices;
    }
}
