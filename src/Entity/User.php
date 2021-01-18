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
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeTrait;
use App\Entity\Traits\UserTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class User extends BaseEntity implements UserInterface
{
    use IdTrait;
    use TimeTrait;
    use UserTrait;

    // can use any features & impersonate users
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    // can use any features
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isAdmin = false;

    /**
     * @var Registration[]|ArrayCollection
     *
     * @ORM\OneToMany (targetEntity="App\Entity\Registration", mappedBy="user")
     */
    private $registrations;

    /**
     * @var Event[]|ArrayCollection
     *
     * @ORM\OneToMany (targetEntity="App\Entity\Event", mappedBy="lecturer")
     */
    private $lectures;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->registrations = new ArrayCollection();
        $this->lectures = new ArrayCollection();
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[] The user roles
     */
    public function getRoles()
    {
        if ($this->isAdmin) {
            return [self::ROLE_ADMIN];
        }

        return [self::ROLE_USER];
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @return Registration[]|ArrayCollection
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    /**
     * @return Event[]|ArrayCollection
     */
    public function getLectures()
    {
        return $this->lectures;
    }
}
