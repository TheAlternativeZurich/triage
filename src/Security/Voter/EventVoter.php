<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Voter;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EventVoter extends Voter
{
    public const EVENT_REGISTER = 'EVENT_REGISTER';
    public const EVENT_CREATE = 'EVENT_CREATE';
    public const EVENT_EDIT = 'EVENT_EDIT';
    public const EVENT_MODERATE = 'EVENT_MODERATE';

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param Event  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        if (self::EVENT_MODERATE === $attribute) {
            return true;
        }

        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::EVENT_REGISTER, self::EVENT_CREATE, self::EVENT_EDIT, self::EVENT_MODERATE])) {
            return false;
        }

        if (in_array($attribute, [self::EVENT_CREATE, self::EVENT_MODERATE])) {
            return true;
        }

        return $subject instanceof Event;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param Event  $subject
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('Unknown user payload '.serialize($user).'!');
        }

        $userIsAdmin = in_array(User::ROLE_ADMIN, $user->getRoles());

        if ($userIsAdmin) {
            return true;
        }

        if (self::EVENT_MODERATE === $attribute) {
            return false;
        }

        switch ($attribute) {
            case self::EVENT_CREATE:
                return $user->getIsEnabled();
            case self::EVENT_EDIT:
                return $subject->getLecturer() === $user;
            case self::EVENT_REGISTER:
                return $subject->isPublic();
        }

        throw new \LogicException('Unknown attribute '.$attribute.'!');
    }
}
