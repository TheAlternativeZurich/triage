<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture implements OrderedFixtureInterface
{
    public const ORDER = 1;

    public const LECTURER_REFERENCE = 'LECTURER';
    public const USER_REFERENCE = 'USER';

    public function load(ObjectManager $manager)
    {
        $admins = [
            ['f@thealternative.ch', 'asdf', self::LECTURER_REFERENCE],
            ['u@thealternative.ch', 'asdf', self::USER_REFERENCE],
        ];

        foreach ($admins as $entry) {
            $user = new User();
            $user->setEmail($entry[0]);
            $user->setPasswordFromPlain($entry[1]);
            $user->setIsEnabled(true);
            $user->setIsAdmin(true);

            $this->setReference($entry[2], $user);
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return self::ORDER;
    }
}
