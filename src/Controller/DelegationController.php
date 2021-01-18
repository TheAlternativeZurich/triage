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
use App\Controller\Traits\ReviewableContentEditTrait;
use App\Entity\Delegation;
use App\Enum\ParticipantRole;
use App\Form\Event\AddMultipleDelegationsType;
use App\Form\Event\EditEventType;
use App\Form\Event\RemoveDelegationType;
use App\Security\Voter\DelegationVoter;
use App\Service\Interfaces\ExportServiceInterface;
use App\Service\Interfaces\FileServiceInterface;
use App\Service\Interfaces\InvoiceServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/delegation")
 */
class DelegationController extends BaseDoctrineController
{
    /**
     * @Route("/new", name="delegation_new")
     *
     * @return Response
     */
    public function newAction(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE);

        $form = $this->createForm(AddMultipleDelegationsType::class);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'delegation', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $commaSeparatedDelegationNames = $form->get('commaSeparatedDelegationNames')->getData();
            $delegationNames = explode(',', $commaSeparatedDelegationNames);

            $existingDelegations = $this->getDoctrine()->getRepository(Delegation::class)->findAll();
            $existingDelegationNamesLowercase = array_map(function (Delegation $delegation) {
                return strtolower($delegation->getName());
            }, $existingDelegations);

            $delegations = [];
            foreach ($delegationNames as $delegationName) {
                $cleanedDelegationName = trim($delegationName);
                if (0 === strlen($cleanedDelegationName) || in_array(strtolower($cleanedDelegationName), $existingDelegationNamesLowercase)) {
                    continue;
                }

                $delegation = new Delegation();
                $delegation->setName($cleanedDelegationName);
                $delegations[] = $delegation;
            }

            $this->fastSave(...$delegations);

            $message = $translator->trans('new.success.created', ['%count%' => count($delegations)], 'delegation');
            $this->displaySuccess($message);

            return $this->redirectToRoute('index');
        }

        return $this->render('delegation/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/view/{delegation}", name="delegation_view")
     *
     * @return Response
     */
    public function viewAction(Delegation $delegation, InvoiceServiceInterface $invoiceService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_VIEW, $delegation);
        $invoice = $invoiceService->getInvoice($delegation);

        return $this->render('delegation/view.html.twig', ['delegation' => $delegation, 'invoice' => $invoice]);
    }

    /**
     * @Route("/invoice/{delegation}", name="delegation_invoice")
     *
     * @return Response
     */
    public function invoiceAction(Delegation $delegation, InvoiceServiceInterface $invoiceService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_VIEW, $delegation);
        $invoice = $invoiceService->getInvoice($delegation);

        return $this->render('delegation/invoice.html.twig', ['delegation' => $delegation, 'invoice' => $invoice]);
    }

    /**
     * @Route("/users/{delegation}", name="delegation_users")
     *
     * @return Response
     */
    public function usersAction(Delegation $delegation)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_VIEW, $delegation);

        return $this->render('delegation/users.html.twig', ['delegation' => $delegation]);
    }

    use ReviewableContentEditTrait;

    /**
     * @Route("/edit_attendance/{delegation}", name="delegation_edit_attendance")
     *
     * @return Response
     */
    public function editAttendanceAction(Request $request, Delegation $delegation, TranslatorInterface $translator)
    {
        $validation = function () use ($delegation, $translator) {
            return $this->validateAttendance($delegation, $translator);
        };

        return $this->editReviewableDelegationContent($request, $translator, $delegation, 'attendance', $validation);
    }

    private function validateAttendance(Delegation $delegation, TranslatorInterface $translator)
    {
        $result = ($delegation->getLeaderCount() > 0 || !$delegation->getParticipantWithRole(ParticipantRole::LEADER)) &&
            ($delegation->getLeaderCount() > 1 || !$delegation->getParticipantWithRole(ParticipantRole::DEPUTY_LEADER)) &&
            !$delegation->getParticipantWithRole(ParticipantRole::CONTESTANT, $delegation->getContestantCount()) &&
            !$delegation->getParticipantWithRole(ParticipantRole::GUEST, $delegation->getGuestCount());

        if (!$result) {
            $message = $translator->trans('edit_attendance.error.too_few_spaces', [], 'delegation');
            $this->displayError($message);

            return false;
        }

        return true;
    }

    /**
     * @Route("/edit_contribution/{delegation}", name="delegation_edit_contribution")
     *
     * @return Response
     */
    public function editContributionAction(Request $request, Delegation $delegation, TranslatorInterface $translator)
    {
        return $this->editReviewableDelegationContent($request, $translator, $delegation, 'contribution');
    }

    /**
     * @Route("/review_attendance/{delegation}", name="delegation_review_attendance")
     *
     * @return Response
     */
    public function reviewAttendanceAction(Request $request, Delegation $delegation, TranslatorInterface $translator)
    {
        $validation = function () use ($delegation, $translator) {
            return $this->validateAttendance($delegation, $translator);
        };

        return $this->reviewDelegationContent($request, $translator, $delegation, 'attendance', $validation);
    }

    /**
     * @Route("/review_contribution/{delegation}", name="delegation_review_contribution")
     *
     * @return Response
     */
    public function reviewContributionAction(Request $request, Delegation $delegation, TranslatorInterface $translator)
    {
        return $this->reviewDelegationContent($request, $translator, $delegation, 'contribution');
    }

    /**
     * @Route("/registration_regenerate/{delegation}/", name="delegation_registration_regenerate")
     *
     * @return Response
     */
    public function registrationRegenerateAction(Delegation $delegation, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE, $delegation);

        $delegation->generateRegistrationHash();
        $this->fastSave($delegation);

        $message = $translator->trans('registration_regenerate.success.regenerated', [], 'delegation');
        $this->displaySuccess($message);

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/registration_regenerate_all/", name="delegation_registration_regenerate_all")
     *
     * @return Response
     */
    public function registrationRegenerateAllAction(TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE);

        $delegations = $this->getDoctrine()->getRepository(Delegation::class)->findAll();
        foreach ($delegations as $delegation) {
            $delegation->generateRegistrationHash();
        }
        $this->fastSave(...$delegations);

        $message = $translator->trans('registration_regenerate_all.success.regenerated', [], 'delegation');
        $this->displaySuccess($message);

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/edit_finance/{delegation}", name="delegation_edit_finance")
     *
     * @return Response
     */
    public function editFinanceAction(Request $request, Delegation $delegation, TranslatorInterface $translator, InvoiceServiceInterface $invoiceService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE, $delegation);

        $form = $this->createForm(EditEventType::class, $delegation, ['required' => false]);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'delegation', 'label' => 'edit_finance.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->fastSave($delegation);

            $message = $translator->trans('edit_finance.success.edited', [], 'delegation');
            $this->displaySuccess($message);

            return $this->redirectToRoute('index');
        }

        $invoice = $invoiceService->getInvoice($delegation);

        return $this->render('delegation/edit_finance.html.twig', ['form' => $form->createView(), 'invoice' => $invoice]);
    }

    /**
     * @Route("/remove/{delegation}/", name="delegation_remove")
     *
     * @return Response
     */
    public function removeAction(Request $request, Delegation $delegation, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE, $delegation);

        $form = $this->createForm(RemoveDelegationType::class);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'delegation', 'label' => 'remove.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $toRemove = [$delegation];
            foreach ($delegation->getParticipants() as $participant) {
                $toRemove[] = $participant;
                $fileService->removeFiles($participant);
            }
            foreach ($delegation->getUsers() as $user) {
                $toRemove[] = $user;
            }
            $this->fastRemove(...$toRemove);

            $message = $translator->trans('remove.success.removed', [], 'delegation');
            $this->displaySuccess($message);

            return $this->redirectToRoute('index');
        }

        return $this->render('delegation/remove.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/export", name="delegation_export")
     *
     * @return Response
     */
    public function exportAction(ExportServiceInterface $exportService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE);

        $delegations = $this->getDoctrine()->getRepository(Delegation::class)->findBy([], ['name' => 'ASC']);

        return $exportService->exportToCsv($delegations, 'delegation-export', 'delegations');
    }
}
