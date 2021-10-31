<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Entity\Base\BaseEntity;
use App\Entity\Traits\EventTrait;
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class Event extends BaseEntity
{
    use IdTrait;
    use TimeTrait;
    use EventTrait;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $identifier;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $experience;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @Assert\Range(min="0", max="100")
     */
    private $minRegistrations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $public = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true, options={"default" : null})
     */
    private $publicNotificationSent;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true, options={"default" : null})
     */
    private $sufficientRegistrationsNotificationSent;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="lectures")
     */
    private $lecturer;

    /**
     * @var Registration[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Registration", mappedBy="event")
     * @ORM\OrderBy({"createdAt" = "ASC"})
     */
    private $registrations;

    public function __construct()
    {
        $this->registrations = new ArrayCollection();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): void
    {
        $this->experience = $experience;
    }

    public function getMinRegistrations(): ?int
    {
        return $this->minRegistrations;
    }

    public function setMinRegistrations(?int $minRegistrations): void
    {
        $this->minRegistrations = $minRegistrations;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getPublicNotificationSent(): ?\DateTime
    {
        return $this->publicNotificationSent;
    }

    public function setPublicNotificationSent(?\DateTime $publicNotificationSent): void
    {
        $this->publicNotificationSent = $publicNotificationSent;
    }

    public function getSufficientRegistrationsNotificationSent(): ?\DateTime
    {
        return $this->sufficientRegistrationsNotificationSent;
    }

    public function setSufficientRegistrationsNotificationSent(?\DateTime $sufficientRegistrationsNotificationSent): void
    {
        $this->sufficientRegistrationsNotificationSent = $sufficientRegistrationsNotificationSent;
    }

    public function getLecturer(): ?User
    {
        return $this->lecturer;
    }

    public function setLecturer(?User $lecturer): void
    {
        $this->lecturer = $lecturer;
    }

    /**
     * @return Registration[]|ArrayCollection
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    public function sufficientRegistrations(): bool
    {
        return 0 === $this->missingRegistrations();
    }

    public function missingRegistrations(): int
    {
        return max(0, $this->minRegistrations - $this->registrations->count());
    }

    public function getRegistrationForUser(?User $user): ?Registration
    {
        if (!$user) {
            return null;
        }

        foreach ($this->registrations as $registration) {
            if ($registration->getUser() === $user) {
                return $registration;
            }
        }

        return null;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function canRegister()
    {
        $now = new \DateTime('now');

        return $now < $this->startDate;
    }

    public function canDeregister()
    {
        return $this->canRegister();
    }
}
