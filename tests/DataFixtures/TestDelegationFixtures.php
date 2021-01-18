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

use App\Entity\Delegation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TestDelegationFixtures extends Fixture implements OrderedFixtureInterface
{
    public const ORDER = 0;
    public const DELEGATION_NAME = 'CH';

    public function load(ObjectManager $manager)
    {
        $delegation = new Delegation();
        $delegation->setName(self::DELEGATION_NAME);
        $manager->persist($delegation);

        $manager->flush();
    }

    public function getOrder()
    {
        return self::ORDER;
    }
}
