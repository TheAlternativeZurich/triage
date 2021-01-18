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
use App\Entity\Participant;
use App\Enum\ParticipantRole;
use App\Form\Participant\EditParticipantType;
use App\Form\Participant\RemoveParticipantType;
use App\Form\Traits\EditParticipantPersonalDataType;
use App\Security\Voter\DelegationVoter;
use App\Security\Voter\ParticipantVoter;
use App\Service\Interfaces\ExportServiceInterface;
use App\Service\Interfaces\FileServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/participant")
 */
class ParticipantController extends BaseDoctrineController
{
    /**
     * @Route("/new/{delegation}/{role}", name="participant_new")
     *
     * @return Response
     */
    public function newAction(Request $request, Delegation $delegation, int $role, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_EDIT, $delegation);

        if (!$this->canRoleBeChosen($role, $delegation, $delegation->getParticipants()->toArray())) {
            throw new BadRequestException();
        }

        $participant = new Participant();
        $participant->setRole($role);
        $participant->setDelegation($delegation);
        $participant->setCountryOfResidence($delegation->getName());
        $participant->setNationality($delegation->getName());
        $participant->setPassportIssueCountry($delegation->getName());

        $form = $this->createForm(EditParticipantPersonalDataType::class, $participant);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'participant', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->fastSave($participant);
            $this->processImages($form, $participant, $translator, $fileService);

            $roleTranslation = ParticipantRole::getTranslationForValue($role, $translator);
            $message = $translator->trans('new.success.created', ['%role%' => $roleTranslation], 'participant');
            $this->displaySuccess($message);

            return $this->redirectToRoute('delegation_view', ['delegation' => $delegation->getId()]);
        }

        return $this->render('participant/new.html.twig', ['form' => $form->createView(), 'role' => $role, 'delegation' => $delegation]);
    }

    private function processImages(FormInterface $form, Participant $participant, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $file = $form->get('portraitFile')->getData();
        if ($file instanceof UploadedFile) {
            if (!$fileService->replacePortrait($participant, $file)) {
                $message = $translator->trans('new.error.portrait_upload_failed', [], 'participant');
                $this->displayError($message);
            }
        }

        $file = $form->get('papersFile')->getData();
        if ($file instanceof UploadedFile) {
            if (!$fileService->replacePapers($participant, $file)) {
                $message = $translator->trans('new.error.papers_upload_failed', [], 'participant');
                $this->displayError($message);
            }
        }

        $file = $form->get('consentFile')->getData();
        if ($file instanceof UploadedFile) {
            if (!$fileService->replaceConsent($participant, $file)) {
                $message = $translator->trans('new.error.consent_upload_failed', [], 'participant');
                $this->displayError($message);
            }
        }

        $this->fastSave($participant);
    }

    /**
     * @Route("/download/{participant}/{type}/{filename}", name="participant_download")
     *
     * @return Response
     */
    public function downloadAction(Participant $participant, string $type, string $filename, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_EDIT, $participant);

        switch ($type) {
            case FileServiceInterface::PORTRAIT:
                return $fileService->downloadPortrait($participant, $filename);
            case FileServiceInterface::PAPERS:
                return $fileService->downloadPapers($participant, $filename);
            case FileServiceInterface::CONSENT:
                return $fileService->downloadConsent($participant, $filename);
            default:
                throw new NotFoundHttpException();
        }
    }

    /**
     * @Route("/download_archive/{type}", name="participant_download_archive")
     *
     * @return Response
     */
    public function downloadArchiveAction(string $type, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_MODERATE);

        return $fileService->downloadArchive($type);
    }

    use ReviewableContentEditTrait;

    /**
     * @Route("/edit_personal_data/{participant}", name="participant_edit_personal_data")
     *
     * @return Response
     */
    public function editPersonalDataAction(Request $request, Participant $participant, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $validator = function (FormInterface $form) use ($participant, $translator, $fileService) {
            $this->processImages($form, $participant, $translator, $fileService);

            return true;
        };

        return $this->editReviewableParticipantContent($request, $translator, $participant, 'personal_data', $validator);
    }

    /**
     * @Route("/review_personal_data/{participant}", name="participant_review_personal_data")
     *
     * @return Response
     */
    public function reviewPersonalDataAction(Request $request, Participant $participant, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $validator = function (FormInterface $form) use ($participant, $translator, $fileService) {
            $this->processImages($form, $participant, $translator, $fileService);

            return true;
        };

        return $this->reviewParticipantContent($request, $translator, $participant, 'personal_data', $validator);
    }

    /**
     * @Route("/edit_immigration/{participant}", name="participant_edit_immigration")
     *
     * @return Response
     */
    public function editImmigrationAction(Request $request, Participant $participant, TranslatorInterface $translator)
    {
        return $this->editReviewableParticipantContent($request, $translator, $participant, 'immigration');
    }

    /**
     * @Route("/review_immigration/{participant}", name="participant_review_immigration")
     *
     * @return Response
     */
    public function reviewImmigrationAction(Request $request, Participant $participant, TranslatorInterface $translator)
    {
        return $this->reviewParticipantContent($request, $translator, $participant, 'immigration');
    }

    /**
     * @Route("/edit_event_presence/{participant}", name="participant_edit_event_presence")
     *
     * @return Response
     */
    public function editEventPresenceAction(Request $request, Participant $participant, TranslatorInterface $translator)
    {
        return $this->editReviewableParticipantContent($request, $translator, $participant, 'event_presence');
    }

    /**
     * @Route("/review_event_presence/{participant}", name="participant_review_event_presence")
     *
     * @param FileServiceInterface $fileService
     *
     * @return Response
     */
    public function reviewEventPresenceAction(Request $request, Participant $participant, TranslatorInterface $translator)
    {
        return $this->reviewParticipantContent($request, $translator, $participant, 'event_presence');
    }

    /**
     * @Route("/remove/{participant}/", name="participant_remove")
     *
     * @return Response
     */
    public function removeAction(Request $request, Participant $participant, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_REMOVE, $participant);

        $form = $this->createForm(RemoveParticipantType::class);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'participant', 'label' => 'remove.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $fileService->removeFiles($participant);
            $this->fastRemove($participant);

            $roleTranslation = ParticipantRole::getTranslationForValue($participant->getRole(), $translator);
            $message = $translator->trans('remove.success.removed', ['%role%' => $roleTranslation], 'participant');
            $this->displaySuccess($message);

            return $this->redirectToRoute('delegation_view', ['delegation' => $participant->getDelegation()->getId()]);
        }

        return $this->render('participant/remove.html.twig', ['form' => $form->createView(), 'role' => $participant->getRole(), 'delegation' => $participant->getDelegation()]);
    }

    /**
     * @Route("/export", name="participant_export")
     *
     * @return Response
     */
    public function exportAction(ExportServiceInterface $exportService)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_MODERATE);

        $participants = $this->getDoctrine()->getRepository(Participant::class)->findBy([], ['role' => 'ASC', 'familyName' => 'ASC']);
        $participantsByDelegationName = [];
        foreach ($participants as $participant) {
            $participantsByDelegationName[$participant->getDelegation()->getName()][] = $participant;
        }
        ksort($participantsByDelegationName);
        $participants = array_merge(...array_values($participantsByDelegationName));

        return $exportService->exportToCsv($participants, 'participant-export', 'participants');
    }

    /**
     * @Route("/edit/{participant}", name="participant_edit")
     *
     * @return Response
     */
    public function editAction(Request $request, Participant $participant, TranslatorInterface $translator, FileServiceInterface $fileService)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_MODERATE, $participant);

        $form = $this->createForm(EditParticipantType::class, $participant, ['required' => false]);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'participant', 'label' => 'edit.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $delegation = $participant->getDelegation();
            $otherParticipants = array_filter($delegation->getParticipants()->toArray(), function (Participant $other) use ($participant) {
                return $other !== $participant;
            });
            if ($this->canRoleBeChosen($participant->getRole(), $delegation, $otherParticipants)) {
                $this->fastSave($participant);
                $this->processImages($form->get('personalData'), $participant, $translator, $fileService);

                $roleTranslation = ParticipantRole::getTranslationForValue($participant->getRole(), $translator);
                $message = $translator->trans('edit.success.edited', ['%role%' => $roleTranslation], 'participant');
                $this->displaySuccess($message);

                return $this->redirectToRoute('index');
            } else {
                $roleTranslation = ParticipantRole::getTranslationForValue($participant->getRole(), $translator);
                $message = $translator->trans('edit.error.role_already_taken', ['%role%' => $roleTranslation], 'participant');
                $this->displayError($message);
            }
        }

        return $this->render('participant/edit.html.twig', ['form' => $form->createView(), 'role' => $participant->getRole()]);
    }

    private function canRoleBeChosen(int $role, Delegation $delegation, array $participants): bool
    {
        if ($role > 3 || $role < 0) {
            return false;
        }

        $sameRoleCount = 0;

        foreach ($participants as $participant) {
            if ($role !== $participant->getRole()) {
                continue;
            }

            ++$sameRoleCount;
        }

        switch ($role) {
            case ParticipantRole::LEADER:
                $result = 0 === $sameRoleCount;
                break;
            case ParticipantRole::DEPUTY_LEADER:
                $result = 0 === $sameRoleCount && $delegation->getLeaderCount() > 1; // only allow deputy leader if two leaders chosen
                break;
            case ParticipantRole::CONTESTANT:
                $result = $delegation->getContestantCount() > $sameRoleCount;
                break;
            default:
                $result = $delegation->getGuestCount() > $sameRoleCount;
        }

        if (!$result) {
            return false;
        }

        return true;
    }
}
