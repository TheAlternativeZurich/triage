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
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Serializer\SerializerInterface;

class EventFixture extends Fixture implements OrderedFixtureInterface
{
    const ORDER = UserFixture::ORDER + 1;

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
        $eventsJson = file_get_contents(__DIR__.'/Resources/events.json');
        /** @var Event[] $events */
        $events = $this->serializer->deserialize($eventsJson, Event::class.'[]', 'json');
        /** @var User $lecturer */
        $lecturer = $this->getReference(UserFixture::LECTURER_REFERENCE);
        foreach ($events as $event) {
            $event->setLecturer($lecturer);

            $manager->persist($event);
        }

        $manager->flush();
    }

    public function getOrder()
    {
        return self::ORDER;
    }
}
