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
use App\Entity\User;
use App\Form\User\RegisterType;
use App\Form\UserTrait\LoginType;
use App\Form\UserTrait\OnlyEmailType;
use App\Form\UserTrait\SetPasswordType;
use App\Security\LoginFormAuthenticator;
use App\Service\Interfaces\EmailServiceInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends BaseDoctrineController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger, TranslatorInterface $translator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('index');
        }

        // show last auth error
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error instanceof DisabledException) {
            $this->displayError($translator->trans('login.errors.account_disabled', [], 'security'));
        } elseif ($error instanceof BadCredentialsException) {
            $this->displayError($translator->trans('login.errors.password_wrong', [], 'security'));
        } elseif ($error instanceof UsernameNotFoundException) {
            $this->displayError($translator->trans('login.errors.email_not_found', [], 'security'));
        } elseif (null !== $error) {
            $this->displayError($translator->trans('login.errors.login_failed', [], 'security'));
            $logger->error('login failed', ['exception' => $error]);
        }

        $user = new User();
        $user->setEmail($authenticationUtils->getLastUsername());

        $form = $this->createForm(LoginType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'login.submit']);

        return $this->render('security/login.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/setup", name="setup")
     *
     * @return Response
     */
    public function setupAction(Request $request, TranslatorInterface $translator, LoginFormAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler)
    {
        $existingAdmins = $this->getDoctrine()->getRepository(User::class)->findBy(['isAdmin' => true]);
        if (count($existingAdmins) > 0) {
            $this->displayError($translator->trans('setup.error.already_setup', [], 'security'));

            return $this->redirect('login');
        }

        $user = new User();
        $user->setIsAdmin(true);
        $user->setIsEnabled(true);

        $form = $this->createForm(RegisterType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'setup.submit']);

        if ($this->handleSetPasswordForm($form, $request, $user, $translator)) {
            $this->fastSave($user);
            $this->loginUser($user, $authenticator, $guardHandler, $request);

            $this->displaySuccess($translator->trans('setup.success.welcome', [], 'security'));

            return $this->redirectToRoute('index');
        }

        return $this->render('security/setup.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/register", name="register")
     *
     * @return Response
     */
    public function registerAction(Request $request, TranslatorInterface $translator, EmailServiceInterface $emailService)
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'register.submit']);

        if ($this->handleSetPasswordForm($form, $request, $user, $translator)) {
            if ($this->tryRegisterUser($user, $translator, $emailService)) {
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('security/register.html.twig', ['form' => $form->createView()]);
    }

    private function tryRegisterUser(User $user, TranslatorInterface $translator, EmailServiceInterface $emailService)
    {
        $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
        if (null !== $existingUser) {
            $message = $translator->trans('register.error.email_already_used', [], 'security');
            $this->displayError($message);

            return false;
        }

        $user->generateAuthenticationHash();
        $this->fastSave($user);

        if (!$emailService->sendRegisterConfirmLink($user)) {
            $this->displayError($translator->trans('register.error.email_already_used', [], 'security'));

            return false;
        }

        $this->displaySuccess($translator->trans('register.success.email_sent', [], 'security'));

        return true;
    }

    /**
     * @Route("/register/confirm/{authenticationHash}", name="register_confirm")
     *
     * @return Response
     */
    public function registerConfirmAction(Request $request, $authenticationHash, TranslatorInterface $translator, LoginFormAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler)
    {
        if (!($user = $this->getUserFromAuthenticationHash($authenticationHash, $translator))) {
            return $this->redirectToRoute('login');
        }

        $user->setIsEnabled(true);
        $user->generateAuthenticationHash();
        $this->fastSave($user);

        $this->displaySuccess($translator->trans('register.success.registration_confirm', [], 'security'));
        $this->loginUser($user, $authenticator, $guardHandler, $request);

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/recover", name="recover")
     *
     * @return Response
     */
    public function recoverAction(Request $request, EmailServiceInterface $emailService, TranslatorInterface $translator, LoggerInterface $logger)
    {
        $user = new User();
        $form = $this->createForm(OnlyEmailType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'recover.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $existingUser */
            $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if (null === $existingUser) {
                $logger->info('could not reset password of unknown user '.$user->getEmail());
                $this->displayError($translator->trans('recover.fail.email_not_found', [], 'security'));
            } else {
                $this->sendAuthenticationLink($existingUser, $emailService, $logger, $translator);
            }
        }

        return $this->render('security/recover.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/recover/confirm/{authenticationHash}", name="recover_confirm")
     *
     * @param $authenticationHash
     *
     * @return Response
     */
    public function recoverConfirmAction(Request $request, $authenticationHash, TranslatorInterface $translator, LoginFormAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler)
    {
        if (!($user = $this->getUserFromAuthenticationHash($authenticationHash, $translator))) {
            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(SetPasswordType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'recover_confirm.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->ensurePasswordsConformity($form, $user, $translator)) {
            $user->generateAuthenticationHash();
            $this->fastSave($user);

            $message = $translator->trans('recover_confirm.success.password_set', [], 'security');
            $this->displaySuccess($message);

            $this->loginUser($user, $authenticator, $guardHandler, $request);

            return $this->redirectToRoute('index');
        }

        return $this->render('security/recover_confirm.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function ensurePasswordsConformity(FormInterface $form, User $user, TranslatorInterface $translator): bool
    {
        $plainPassword = $form->get('plainPassword')->getData();
        $repeatPlainPassword = $form->get('repeatPlainPassword')->getData();

        if (strlen($plainPassword) < 8) {
            $this->displayError($translator->trans('recover_confirm.error.password_too_short', [], 'security'));

            return false;
        }

        if ($plainPassword !== $repeatPlainPassword) {
            $this->displayError($translator->trans('recover_confirm.error.passwords_do_not_match', [], 'security'));

            return false;
        }

        $user->setPasswordFromPlain($plainPassword);

        return true;
    }

    private function getUserFromAuthenticationHash(string $authenticationHash, TranslatorInterface $translator): ?User
    {
        /** @var User|null $user */
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['authenticationHash' => $authenticationHash]);
        if (null === $user) {
            $this->displayError($translator->trans('recover_confirm.error.invalid_hash', [], 'security'));

            return null;
        }

        return $user;
    }

    private function loginUser(UserInterface $user, LoginFormAuthenticator $authenticator, GuardAuthenticatorHandler $guardHandler, Request $request)
    {
        // after validating the user and saving them to the database
        // authenticate the user and use onAuthenticationSuccess on the authenticator
        return $guardHandler->authenticateUserAndHandleSuccess(
            $user,          // the User object you just created
            $request,
            $authenticator, // authenticator whose onAuthenticationSuccess you want to use
            'main'          // the name of your firewall in security.yaml
        );
    }

    private function sendAuthenticationLink(User $existingUser, EmailServiceInterface $emailService, LoggerInterface $logger, TranslatorInterface $translator): void
    {
        $existingUser->generateAuthenticationHash();
        $this->fastSave($existingUser);

        if ($emailService->sendRecoverConfirmLink($existingUser)) {
            $logger->info('sent password reset email to '.$existingUser->getEmail());
            $this->displaySuccess($translator->trans('recover.success.email_sent', [], 'security'));
        } else {
            $logger->error('could not send password reset email '.$existingUser->getEmail());
            $this->displayError($translator->trans('recover.fail.email_not_sent', [], 'security'));
        }
    }

    private function handleSetPasswordForm(FormInterface $form, Request $request, User $user, TranslatorInterface $translator): bool
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->ensurePasswordsConformity($form->get('password'), $user, $translator)) {
            return true;
        }

        return false;
    }
}
