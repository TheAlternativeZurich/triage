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
use App\Entity\TravelGroup;
use App\Enum\ArrivalOrDeparture;
use App\Form\TravelGroup\EditTravelGroupType;
use App\Form\TravelGroup\RemoveTravelGroupType;
use App\Security\Voter\DelegationVoter;
use App\Security\Voter\TravelGroupVoter;
use App\Service\Interfaces\ExportServiceInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/travel_group")
 */
class TravelGroupController extends BaseDoctrineController
{
    /**
     * @Route("/new/{delegation}/{arrivalOrDeparture}", name="travel_group_new")
     *
     * @return Response
     */
    public function newAction(Request $request, Delegation $delegation, int $arrivalOrDeparture, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_EDIT, $delegation);

        if (ArrivalOrDeparture::ARRIVAL !== $arrivalOrDeparture && ArrivalOrDeparture::DEPARTURE !== $arrivalOrDeparture) {
            throw new BadRequestException();
        }

        $travelGroup = new TravelGroup();
        $travelGroup->setDelegation($delegation);
        $travelGroup->setArrivalOrDeparture($arrivalOrDeparture);
        foreach ($delegation->getParticipantsWithoutTravelGroup($arrivalOrDeparture) as $participantWithoutTravelGroup) {
            $travelGroup->addParticipant($participantWithoutTravelGroup);
        }

        $form = $this->createForm(EditTravelGroupType::class, $travelGroup);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'travel_group', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->fastSave($travelGroup);

            $message = $translator->trans('new.success.created', [], 'travel_group');
            $this->displaySuccess($message);

            return $this->redirectToRoute('delegation_view', ['delegation' => $delegation->getId()]);
        }

        return $this->render('travel_group/new.html.twig', ['form' => $form->createView(), 'delegation' => $delegation]);
    }

    use ReviewableContentEditTrait;

    /**
     * @Route("/edit/{travelGroup}", name="travel_group_edit")
     *
     * @return Response
     */
    public function editAction(Request $request, TravelGroup $travelGroup, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(TravelGroupVoter::TRAVEL_GROUP_EDIT, $travelGroup);

        return $this->editReviewableContent($request, $translator, $travelGroup->getDelegation(), $travelGroup, 'travel_group');
    }

    /**
     * @Route("/review/{travelGroup}", name="travel_group_review")
     *
     * @return Response
     */
    public function reviewAction(Request $request, TravelGroup $travelGroup, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(TravelGroupVoter::TRAVEL_GROUP_MODERATE, $travelGroup);

        return $this->reviewReviewableContent($request, $translator, $travelGroup->getDelegation(), $travelGroup, 'travel_group');
    }

    /**
     * @Route("/remove/{travelGroup}/", name="travel_group_remove")
     *
     * @return Response
     */
    public function removeAction(Request $request, TravelGroup $travelGroup, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(TravelGroupVoter::TRAVEL_GROUP_REMOVE, $travelGroup);

        $form = $this->createForm(RemoveTravelGroupType::class);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'travel_group', 'label' => 'remove.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $participants = [];
            foreach ($travelGroup->getParticipants()->toArray() as $participant) {
                $travelGroup->removeParticipant($participant);
                $participants[] = $participant;
            }
            $this->fastSave(...$participants);
            $this->fastRemove($travelGroup);

            $message = $translator->trans('remove.success.removed', [], 'travel_group');
            $this->displaySuccess($message);

            return $this->redirectToRoute('delegation_view', ['delegation' => $travelGroup->getDelegation()->getId()]);
        }

        return $this->render('travel_group/remove.html.twig', ['form' => $form->createView(), 'delegation' => $travelGroup->getDelegation()]);
    }

    /**
     * @Route("/export", name="travel_group_export")
     *
     * @return Response
     */
    public function exportAction(ExportServiceInterface $exportService)
    {
        $this->denyAccessUnlessGranted(TravelGroupVoter::TRAVEL_GROUP_MODERATE);

        $travelGroups = $this->getDoctrine()->getRepository(TravelGroup::class)->findBy([], ['arrivalOrDeparture' => 'ASC', 'dateTime' => 'ASC']);

        return $exportService->exportToCsv($travelGroups, 'travel-group-export', 'travel_group');
    }
}
