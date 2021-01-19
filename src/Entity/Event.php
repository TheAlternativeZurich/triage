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
     * @var string|null
     *
     * @ORM\Column(type="text")
     */
    private $experience;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     */
    private $minRegistrations;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $public = false;

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

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): void
    {
        $this->experience = $experience;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getMinRegistrations(): ?int
    {
        return $this->minRegistrations;
    }

    public function setMinRegistrations(?int $minRegistrations): void
    {
        $this->minRegistrations = $minRegistrations;
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
        return $this->registrations->count() >= $this->minRegistrations;
    }

    public function getRegistrationForUser(User $user): ?Registration
    {
        foreach ($this->registrations as $registration) {
            if ($registration->getUser() === $user) {
                return $registration;
            }
        }

        return null;
    }
}
