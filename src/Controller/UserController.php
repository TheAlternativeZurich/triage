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
use App\Form\User\RemoveUserType;
use App\Security\Voter\UserVoter;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/users")
 */
class UserController extends BaseDoctrineController
{
    /**
     * @Route("/remove/{user}/", name="user_remove")
     *
     * @return Response
     */
    public function removeAction(Request $request, User $user, TranslatorInterface $translator, TokenStorageInterface $tokenStorage)
    {
        $this->denyAccessUnlessGranted(UserVoter::USER_REMOVE, $user);

        $currentUser = $user === $this->getUser();
        if ($currentUser) {
            $message = $translator->trans('remove.danger.current_user', [], 'user');
            $this->displayDanger($message);
        }

        $form = $this->createForm(RemoveUserType::class);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'user', 'label' => 'remove.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $delegation = $user->getDelegation();

            $this->fastRemove($user);

            $message = $translator->trans('remove.success.removed', [], 'user');
            $this->displaySuccess($message);

            if ($currentUser) {
                $tokenStorage->setToken(null);

                return $this->redirectToRoute('login');
            }

            return $this->redirectToRoute('delegation_users', ['delegation' => $delegation->getId()]);
        }

        return $this->render('user/remove.html.twig', ['form' => $form->createView()]);
    }
}
