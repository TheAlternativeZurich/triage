<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TestUserFixtures extends Fixture implements OrderedFixtureInterface
{
    public const ORDER = 1;
    public const USER_ADMIN_EMAIL = 'test@thealternative.ch';
    public const USER_DELEGATION_EMAIL = 'test2@thealternative.ch';

    public function load(ObjectManager $manager)
    {
        $admin = new User();
        $admin->setEmail(self::USER_ADMIN_EMAIL);
        $admin->setPasswordFromPlain('asdf');
        $admin->setIsAdmin(true);
        $admin->setIsEnabled(true);
        $manager->persist($admin);

        $delegationUser = new User();
        $delegationUser->setEmail(self::USER_DELEGATION_EMAIL);
        $delegationUser->setPasswordFromPlain('asdf');
        $delegationUser->setIsEnabled(true);
        $manager->persist($delegationUser);

        $manager->flush();
    }

    public function getOrder()
    {
        return self::ORDER;
    }
}
