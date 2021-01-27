<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class RegistrationRepository extends EntityRepository
{
    public function findOrderedByUser(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.user = :user_id')
            ->setParameter(':user_id', $user->getId())
            ->join('r.event', 'e')
            ->orderBy('e.startDate');

        return $queryBuilder->getQuery()->getResult();
    }
}
