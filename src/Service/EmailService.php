<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Email;
use App\Entity\Event;
use App\Entity\User;
use App\Service\Interfaces\EmailServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailService implements EmailServiceInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestStack
     */
    private $request;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var string
     */
    private $mailerFromEmail;

    /**
     * @var string
     */
    private $supportEmail;

    /**
     * EmailService constructor.
     */
    public function __construct(TranslatorInterface $translator, LoggerInterface $logger, RequestStack $request, ManagerRegistry $registry, MailerInterface $mailer, string $mailerFromEmail, string $supportEmail, RouterInterface $router)
    {
        $this->translator = $translator;
        $this->logger = $logger;
        $this->request = $request;
        $this->manager = $registry->getManager();
        $this->mailer = $mailer;
        $this->mailerFromEmail = $mailerFromEmail;
        $this->supportEmail = $supportEmail;
        $this->router = $router;
    }

    public function sendRegisterConfirmLink(User $user): bool
    {
        $link = $this->router->generate('register_confirm', ['authenticationHash' => $user->getAuthenticationHash()]);
        $entity = Email::create(Email::TYPE_REGISTER_CONFIRM, $user, $link);
        $subject = $this->translator->trans('register_confirm.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($user)
            ->subject($subject)
            ->textTemplate('email/register_confirm.txt.twig')
            ->htmlTemplate('email/register_confirm.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function sendRecoverConfirmLink(User $user): bool
    {
        $link = $this->router->generate('recover_confirm', ['authenticationHash' => $user->getAuthenticationHash()]);
        $entity = Email::create(Email::TYPE_RECOVER_CONFIRM, $user, $link);
        $subject = $this->translator->trans('recover_confirm.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($user)
            ->subject($subject)
            ->textTemplate('email/recover_confirm.txt.twig')
            ->htmlTemplate('email/recover_confirm.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function sendEventCreatedNotification(Event $event): bool
    {
        $link = $this->router->generate('event_moderate', ['event' => $event->getId()]);
        $admins = $this->manager->getRepository(User::class)->findBy(['isAdmin' => true]);

        $successful = count($admins) > 0;
        foreach ($admins as $admin) {
            $entity = Email::create(Email::TYPE_EVENT_CREATED_NOTIFICATION, $admin, $link);
            $subject = $this->translator->trans('event_created.subject', ['%page%' => $this->getCurrentPage()], 'email');

            $message = $this->createTemplatedEmailToUser($admin)
                ->subject($subject)
                ->textTemplate('email/event_created.txt.twig')
                ->htmlTemplate('email/event_created.html.twig')
                ->context($entity->getContext());

            $successful &= $this->sendAndStoreEMail($message, $entity);
        }

        return $successful;
    }

    public function sendEventPublicNotification(Event $event): bool
    {
        $link = $this->router->generate('event_share', ['identifier' => $event->getIdentifier()]);

        $entity = Email::create(Email::TYPE_EVENT_PUBLIC_NOTIFICATION, $event->getLecturer(), $link);
        $subject = $this->translator->trans('event_public.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($event->getLecturer())
            ->subject($subject)
            ->textTemplate('email/event_public.txt.twig')
            ->htmlTemplate('email/event_public.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function sendEventSufficientRegistrationsNotification(Event $event): bool
    {
        $link = $this->router->generate('event_share', ['identifier' => $event->getIdentifier()]);

        $entity = Email::create(Email::TYPE_EVENT_SUFFICIENT_REGISTRATIONS_NOTIFICATION, $event->getLecturer(), $link);
        $subject = $this->translator->trans('event_sufficient_registrations.subject', ['%page%' => $this->getCurrentPage()], 'email');

        $message = $this->createTemplatedEmailToUser($event->getLecturer())
            ->subject($subject)
            ->textTemplate('email/event_sufficient_registrations.txt.twig')
            ->htmlTemplate('email/event_sufficient_registrations.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    public function sendEventNotification(Event $event, User $user, string $message)
    {
        $link = $this->router->generate('event_share', ['identifier' => $event->getIdentifier()]);

        $entity = Email::create(Email::TYPE_EVENT_NOTIFICATION, $user, $link, $message);
        $subject = $this->translator->trans('event.subject', ['%event%' => $event->getTitle()], 'email');

        $bccAddresses = [$user->getEmail()];
        foreach ($event->getRegistrations() as $registration) {
            $bccAddresses[] = $registration->getUser()->getEmail();
        }

        $message = $this->createTemplatedEmail()
            ->to($this->supportEmail)
            ->bcc(...$bccAddresses)
            ->subject($subject)
            ->textTemplate('email/event.txt.twig')
            ->htmlTemplate('email/event.html.twig')
            ->context($entity->getContext());

        return $this->sendAndStoreEMail($message, $entity);
    }

    private function createTemplatedEmailToUser(User $user)
    {
        return $this->createTemplatedEmail()
            ->to($user->getEmail());
    }

    private function createTemplatedEmail()
    {
        return (new TemplatedEmail())
            ->from($this->mailerFromEmail)
            ->replyTo($this->supportEmail)
            ->returnPath($this->supportEmail);
    }

    private function getCurrentPage()
    {
        return $this->request->getCurrentRequest() ? $this->request->getCurrentRequest()->getHttpHost() : 'localhost';
    }

    private function sendAndStoreEMail(TemplatedEmail $email, Email $entity): bool
    {
        try {
            $this->mailer->send($email);

            $this->manager->persist($entity);
            $this->manager->flush();

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('email send failed', ['exception' => $exception, 'email' => $entity]);

            return false;
        }
    }
}
