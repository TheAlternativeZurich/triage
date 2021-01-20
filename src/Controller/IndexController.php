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

use App\Controller\Base\BaseController;
use App\Entity\Event;
use App\Service\Interfaces\ConfigurationServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class IndexController extends BaseController
{
    /**
     * @Route("", name="index")
     *
     * @return Response
     */
    public function indexAction(ConfigurationServiceInterface $configurationService)
    {
        $events = $this->getDoctrine()->getRepository(Event::class)->findBy(['public' => true], ['startDate' => 'ASC']);
        $triagePurpose = $configurationService->getTriagePurpose();

        return $this->render('index.html.twig', ['events' => $events, 'triagePurpose' => $triagePurpose]);
    }

    /**
     * @Route("e/{identifier}", name="event_share", priority="-10")
     *
     * @return Response
     */
    public function shareAction(string $identifier)
    {
        /** @var Event $event */
        $event = $this->getDoctrine()->getRepository(Event::class)->findOneBy(['identifier' => $identifier]);
        if (null === $event) {
            throw new NotFoundHttpException();
        }

        return $this->render('share.html.twig', ['event' => $event]);
    }
}
