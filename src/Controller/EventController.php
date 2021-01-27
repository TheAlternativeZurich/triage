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

use App\Controller\Base\BaseDoctrineController;
use App\Entity\Event;
use App\Entity\Registration;
use App\Form\Event\EditEventType;
use App\Security\Voter\EventVoter;
use App\Service\ConfigurationService;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/events")
 */
class EventController extends BaseDoctrineController
{
    /**
     * @Route("/mine", name="event_mine")
     *
     * @return Response
     */
    public function mineAction(ConfigurationService $configurationService)
    {
        $registrations = $this->getDoctrine()->getRepository(Registration::class)->findOrderedByUser($this->getUser());
        $lectures = $this->getUser()->getLectures();
        $triagePurpose = $configurationService->getTriagePurpose();

        return $this->render('event/mine.html.twig', ['registrations' => $registrations, 'lectures' => $lectures, 'triagePurpose' => $triagePurpose]);
    }

    /**
     * @Route("/moderate", name="event_moderate")
     *
     * @return Response
     */
    public function moderateAction()
    {
        $events = $this->getDoctrine()->getRepository(Event::class)->findBy([], ['public' => 'DESC', 'startDate' => 'ASC']);

        return $this->render('event/moderate.html.twig', ['events' => $events]);
    }

    /**
     * @Route("/register/{event}", name="event_register")
     *
     * @return Response
     */
    public function registerAction(Event $event, TranslatorInterface $translator)
    {
        $existingRegistration = $event->getRegistrationForUser($this->getUser());
        if ($existingRegistration) {
            throw new BadRequestHttpException();
        }

        $registration = Registration::createFromUser($event, $this->getUser());
        $this->fastSave($registration);

        $message = $translator->trans('register.success.registered', [], 'event');
        $this->displaySuccess($message);

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/deregister/{event}", name="event_deregister")
     *
     * @return Response
     */
    public function deregisterAction(Event $event, TranslatorInterface $translator)
    {
        $existingRegistration = $event->getRegistrationForUser($this->getUser());
        if (!$existingRegistration) {
            throw new BadRequestHttpException();
        }

        $this->fastRemove($existingRegistration);

        $message = $translator->trans('deregister.success.deregistered', [], 'event');
        $this->displaySuccess($message);

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/new", name="event_new")
     *
     * @return Response
     */
    public function newAction(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_CREATE);

        $event = new Event();
        $form = $this->createForm(EditEventType::class, $event);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->fastSave($event);

            $message = $translator->trans('new.success.created', [], 'event');
            $this->displaySuccess($message);

            return $this->redirectToRoute('index');
        }

        return $this->render('event/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/edit/{event}", name="event_edit")
     *
     * @return Response
     */
    public function editAction(Request $request, Event $event, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_EDIT, $event);

        $event = new Event();
        $form = $this->createForm(EditEventType::class, $event);
        if ($this->getUser()->isAdmin()) {
            $form->add('public', CheckboxType::class, ['required' => false]);
        }
        $form->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->fastSave($event);

            $message = $translator->trans('edit.success.edited', [], 'event');
            $this->displaySuccess($message);

            if ($event->getLecturer() === $this->getUser()) {
                return $this->redirectToRoute('event_mine');
            } else {
                return $this->redirectToRoute('event_moderate');
            }
        }

        return $this->render('event/edit.html.twig', ['form' => $form->createView()]);
    }
}
