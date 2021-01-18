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

use App\Entity\User;

interface EmailServiceInterface
{
    public function sendRegisterConfirmLink(User $user): bool;

    public function sendRecoverConfirmLink(User $user): bool;
}
