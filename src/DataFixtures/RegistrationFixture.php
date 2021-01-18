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

use App\Entity\Event;
use App\Entity\Registration;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Serializer\SerializerInterface;

class RegistrationFixture extends Fixture implements OrderedFixtureInterface
{
    const ORDER = UserFixture::ORDER;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * EventFixture constructor.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function load(ObjectManager $manager)
    {
        $events = $manager->getRepository(Event::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        // skip first event / user to test empty case
        array_pop($events);
        array_pop($users);

        foreach ($events as $event) {
            foreach ($users as $user) {
                $registration = new Registration();
                $registration->setEvent($event);
                $registration->setUser($user);

                $manager->persist($registration);
            }
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return self::ORDER;
    }
}
