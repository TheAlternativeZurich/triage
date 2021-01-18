<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Interfaces;

use App\Entity\Delegation;
use App\Service\Invoice\Invoice;

interface InvoiceServiceInterface
{
    public function getInvoice(Delegation $delegation): Invoice;

    /**
     * @param Delegation[] $delegations
     *
     * @return Invoice[] indexed by delegation id
     */
    public function getInvoiceByDelegation(array $delegations): array;
}
