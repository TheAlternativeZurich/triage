<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Traits;

/*
 * This file is part of the mangel.io project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Entity\Base\BaseEntity;
use App\Entity\Delegation;
use App\Entity\Participant;
use App\Enum\ReviewProgress;
use App\Security\Voter\DelegationVoter;
use App\Security\Voter\ParticipantVoter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ReviewableContentEditTrait
{
    abstract protected function denyAccessUnlessGranted($attribute, $subject = null, string $message = 'Access Denied.'): void;

    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;

    abstract protected function fastSave(...$entities);

    abstract protected function getDoctrine(): ManagerRegistry;

    abstract protected function displaySuccess(string $message, string $link = null);

    abstract protected function displayWarning(string $message, string $link = null);

    abstract protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse;

    abstract protected function render(string $view, array $parameters = [], Response $response = null): Response;

    abstract protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string;

    abstract protected function redirect(string $url, int $status = 302): RedirectResponse;

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function editReviewableDelegationContent(Request $request, TranslatorInterface $translator, Delegation $delegation, string $editablePart, ?callable $validation = null)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_EDIT, $delegation);

        return $this->editReviewableContent($request, $translator, $delegation, $delegation, 'delegation', $editablePart, $validation);
    }

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function reviewDelegationContent(Request $request, TranslatorInterface $translator, Delegation $delegation, string $editablePart, ?callable $validation = null)
    {
        $this->denyAccessUnlessGranted(DelegationVoter::DELEGATION_MODERATE, $delegation);

        return $this->reviewReviewableContent($request, $translator, $delegation, $delegation, 'delegation', $editablePart, $validation);
    }

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function editReviewableParticipantContent(Request $request, TranslatorInterface $translator, Participant $participant, string $editablePart, ?callable $validation = null)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_EDIT, $participant);

        return $this->editReviewableContent($request, $translator, $participant->getDelegation(), $participant, 'participant', $editablePart, $validation);
    }

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function reviewParticipantContent(Request $request, TranslatorInterface $translator, Participant $participant, string $editablePart, ?callable $validation = null)
    {
        $this->denyAccessUnlessGranted(ParticipantVoter::PARTICIPANT_MODERATE, $participant);

        return $this->reviewReviewableContent($request, $translator, $participant->getDelegation(), $participant, 'participant', $editablePart, $validation);
    }

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function editReviewableContent(Request $request, TranslatorInterface $translator, Delegation $delegation, BaseEntity $entity, string $collection, string $editablePart = '', ?callable $validation = null): Response
    {
        list($templatePrefix, $translationSaveNameKey, $getter, $setter, $formType) = $this->applyConventions($collection, $editablePart);
        $saveName = $translator->trans($translationSaveNameKey, [], $collection);

        $readOnly = ReviewProgress::REVIEWED_AND_LOCKED === $entity->$getter();
        $createForm = function () use ($formType, $entity, $readOnly) {
            $form = $this->createForm($formType, $entity, ['disabled' => $readOnly]);
            if (!$readOnly) {
                $form->add('submit', SubmitType::class, ['translation_domain' => 'reviewable_content', 'label' => 'edit.submit']);
            }

            return $form;
        };

        $form = $createForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && !$readOnly) {
            $entity->$setter(ReviewProgress::EDITED);

            if (!is_callable($validation) || $validation($form)) {
                $this->fastSave($entity);

                $message = $translator->trans('edit.success.saved', ['%save_name%' => $saveName], 'reviewable_content');
                $this->displaySuccess($message);
            }

            $this->getDoctrine()->getManager()->refresh($entity);
            $form = $createForm();
        }

        return $this->render($collection.'/'.$templatePrefix.'.html.twig', ['form' => $form->createView(), 'mode' => 'edit', 'delegation' => $delegation, 'entity' => $entity]);
    }

    /**
     * assumes that $editablePart follows some conventions, then generates & processes form.
     */
    private function reviewReviewableContent(Request $request, TranslatorInterface $translator, Delegation $delegation, BaseEntity $entity, string $collection, string $editablePart = '', ?callable $validation = null): Response
    {
        list($templatePrefix, $translationSaveNameKey, $getter, $setter, $formType) = $this->applyConventions($collection, $editablePart);
        $saveName = $translator->trans($translationSaveNameKey, [], $collection);

        $form = $this->createForm($formType, $entity);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'reviewable_content', 'label' => 'edit.submit']);
        if (ReviewProgress::REVIEWED_AND_LOCKED !== $entity->$getter()) {
            $form->add('submit_and_lock', SubmitType::class, ['translation_domain' => 'reviewable_content', 'label' => 'edit.submit_and_lock']);
        } else {
            $form->add('submit_and_unlock', SubmitType::class, ['translation_domain' => 'reviewable_content', 'label' => 'edit.submit_and_unlock']);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!is_callable($validation) || $validation($form)) {
                if ($form->has('submit_and_lock') && $form->get('submit_and_lock')->isClicked()) {
                    $entity->$setter(ReviewProgress::REVIEWED_AND_LOCKED);
                    $message = $translator->trans('edit.success.saved_and_locked', ['%save_name%' => $saveName], 'reviewable_content');
                    $this->displaySuccess($message);
                } elseif ($form->has('submit_and_unlock') && $form->get('submit_and_unlock')->isClicked()) {
                    $entity->$setter(ReviewProgress::EDITED);
                    $message = $translator->trans('edit.success.saved_and_unlocked', ['%save_name%' => $saveName], 'reviewable_content');
                    $this->displayWarning($message);
                } else {
                    $entity->$setter(ReviewProgress::EDITED);
                    $message = $translator->trans('edit.success.saved', ['%save_name%' => $saveName], 'reviewable_content');
                    $this->displaySuccess($message);
                }

                $this->fastSave($entity);

                $targetUrl = $this->generateUrl('index').'#'.$delegation->getName();

                return $this->redirect($targetUrl);
            }
        }

        return $this->render($collection.'/'.$templatePrefix.'.html.twig', ['form' => $form->createView(), 'mode' => 'review', 'delegation' => $delegation, 'entity' => $entity]);
    }

    private function applyConventions(string $collection, string $editablePart = ''): array
    {
        $collectionPascalCase = str_replace('_', '', ucwords($collection, '_'));
        $templatePrefix = 'edit';
        if (strlen($editablePart) > 0) {
            $templatePrefix .= '_'.$editablePart;
            $editablePartPascalCase = str_replace('_', '', ucwords($editablePart, '_'));
            $folder = 'Traits';
        } else {
            $editablePartPascalCase = '';
            $folder = $collectionPascalCase;
        }

        // conventions
        $getter = 'get'.$editablePartPascalCase.'ReviewProgress';
        $setter = 'set'.$editablePartPascalCase.'ReviewProgress';
        $formType = 'App\Form\\'.$folder.'\\Edit'.$collectionPascalCase.$editablePartPascalCase.'Type';
        $translationSaveNameKey = $templatePrefix.'.save_name';
        // end conventions

        return [$templatePrefix, $translationSaveNameKey, $getter, $setter, $formType];
    }
}
