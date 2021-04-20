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
use App\Form\Event\ModerateEventType;
use App\Helper\IdentifierHelper;
use App\Security\Voter\EventVoter;
use App\Service\ConfigurationService;
use App\Service\Interfaces\ConfigurationServiceInterface;
use App\Service\Interfaces\EmailServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
     * @Route("/all", name="event_all")
     *
     * @return Response
     */
    public function allAction()
    {
        $events = $this->getDoctrine()->getRepository(Event::class)->findBy([], ['startDate' => 'ASC']);

        return $this->render('event/all.html.twig', ['events' => $events]);
    }

    /**
     * @Route("/register/{event}", name="event_register")
     *
     * @return Response
     */
    public function registerAction(Event $event, TranslatorInterface $translator, EmailServiceInterface $emailService)
    {
        $existingRegistration = $event->getRegistrationForUser($this->getUser());
        if ($existingRegistration) {
            throw new BadRequestHttpException();
        }

        $registration = Registration::createFromUser($event, $this->getUser());
        $event->getRegistrations()->add($registration);
        $this->fastSave($registration);

        $message = $translator->trans('register.success.registered', [], 'event');
        $this->displaySuccess($message);

        if ($event->getMinRegistrations() > 0 && $event->getMinRegistrations() <= $event->getRegistrations()->count() && !$event->getSufficientRegistrationsNotificationSent()) {
            $emailService->sendEventSufficientRegistrationsNotification($event);
            $event->setSufficientRegistrationsNotificationSent(new \DateTime());
            $this->fastSave($event);
        }

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
    public function newAction(Request $request, TranslatorInterface $translator, ConfigurationServiceInterface $configurationService, EmailServiceInterface $emailService)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_CREATE);

        $triagePurpose = $configurationService->getTriagePurpose();
        $randomTimestamp = mt_rand($triagePurpose->getStartDate()->getTimestamp(), $triagePurpose->getEndDate()->getTimestamp());
        $proposedDate = new \DateTime();
        $proposedDate->setTimestamp($randomTimestamp);
        $proposedDate->setTime(0, 0);

        $event = new Event();
        $event->setLecturer($this->getUser());
        $event->setMinRegistrations(0);
        $event->setStartDate($proposedDate);

        $form = $this->createForm(EditEventType::class, $event);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $identifier = IdentifierHelper::getHumanReadableIdentifier($event->getTitle());
            $event->setIdentifier($identifier);
            $event->getStartDate()->setTime(17, 15);

            $this->fastSave($event);

            $message = $translator->trans('new.success.created', [], 'event');
            $this->displaySuccess($message);

            $emailService->sendEventCreatedNotification($event);

            return $this->redirectToRoute('index');
        }

        return $this->render('event/new.html.twig', ['form' => $form->createView(), 'triagePurpose' => $triagePurpose]);
    }

    /**
     * @Route("/edit/{event}", name="event_edit")
     *
     * @return Response
     */
    public function editAction(Request $request, Event $event, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_EDIT, $event);

        $form = $this->createForm(EditEventType::class, $event);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'edit.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $event->getStartDate()->setTime(17, 00);
            $this->fastSave($event);

            $message = $translator->trans('edit.success.edited', [], 'event');
            $this->displaySuccess($message);

            return $this->redirectToRoute('event_mine');
        }

        return $this->render('event/edit.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/moderate/{event}", name="event_moderate")
     *
     * @return Response
     */
    public function moderateAction(Request $request, Event $event, TranslatorInterface $translator, EmailServiceInterface $emailService)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_MODERATE, $event);

        $form = $this->createForm(ModerateEventType::class, $event);
        if ($event->isPublic()) {
            $form->add('unpublish', SubmitType::class, ['translation_domain' => 'event', 'label' => 'moderate.unpublish']);
        } else {
            $form->add('publish', SubmitType::class, ['translation_domain' => 'event', 'label' => 'moderate.publish']);
        }
        $form->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'moderate.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->has('unpublish') && $form->get('unpublish')->isClicked()) {
                $event->setPublic(false);
            } elseif ($form->has('publish') && $form->get('publish')->isClicked()) {
                $event->setPublic(true);
                if (!$event->getPublicNotificationSent()) {
                    $emailService->sendEventPublicNotification($event);
                    $event->setPublicNotificationSent(new \DateTime());
                }
            }
            $this->fastSave($event);

            $message = $translator->trans('moderate.success.saved', [], 'event');
            $this->displaySuccess($message);

            return $this->redirectToRoute('event_all');
        }

        return $this->render('event/moderate.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/registrations/{event}", name="event_registrations")
     *
     * @return Response
     */
    public function registrationsAction(Request $request, Event $event, TranslatorInterface $translator, EmailServiceInterface $emailService)
    {
        $this->denyAccessUnlessGranted(EventVoter::EVENT_MODERATE, $event);

        $form = $this->createFormBuilder(['subject' => $translator->trans('event.subject', ['%event%' => $event->getTitle()], 'email')])
            ->add('subject', TextType::class, ['disabled' => true, 'translation_domain' => 'event', 'label' => 'registrations.form.subject'])
            ->add('message', TextareaType::class, ['translation_domain' => 'event', 'label' => 'registrations.form.message', 'help' => 'registrations.form.message_help'])
            ->add('submit', SubmitType::class, ['translation_domain' => 'event', 'label' => 'registrations.form.submit'])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->get('message')->getData();

            if ($emailService->sendEventNotification($event, $this->getUser(), $message)) {
                $message = $translator->trans('registrations.success.sent', [], 'event');
                $this->displaySuccess($message);
            }

            return $this->redirectToRoute('event_all');
        }

        return $this->render('event/registrations.html.twig', ['form' => $form->createView(), 'registrations' => $event->getRegistrations()]);
    }
}
