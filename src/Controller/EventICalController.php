<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Event;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EventICalController extends AbstractController
{
    /**
     * @Route("/event/ical/{identifier}", name="event_i_cal")
     */
    public function downloadOneAction(string $identifier): Response
    {
        /** @var Event $event */
        $event = $this->getDoctrine()->getRepository(Event::class)->findOneBy(['identifier' => $identifier]);
        if (null === $event) {
            throw new NotFoundHttpException();
        }

        $duration = 45 * $event->getParts();
        $occurrence = new TimeSpan(
            new \Eluceo\iCal\Domain\ValueObject\DateTime($event->getStartDate(), true),
            new \Eluceo\iCal\Domain\ValueObject\DateTime($event->getStartDate()->modify('+'.$duration.' min'), true)
        );

        $iCalEvent = (new \Eluceo\iCal\Domain\Entity\Event())
            ->setSummary($event->getTitle())
            ->setDescription($event->getDescription())
            ->setOccurrence($occurrence);

        // 2. Create Calendar domain entity.
        $icalCalendar = new Calendar([$iCalEvent]);

        // 3. Transform domain entity into an iCalendar component
        $componentFactory = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($icalCalendar);

        // 4. Set HTTP Headers & Output
        return new Response($calendarComponent, Response::HTTP_OK, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cal.ics"',
        ]);
    }
}
