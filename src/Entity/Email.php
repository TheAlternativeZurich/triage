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
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\UuidV4;

/**
 * An Email is a sent email to the specified receivers.
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Email extends BaseEntity
{
    use IdTrait;

    public const TYPE_REGISTER_CONFIRM = 0;
    public const TYPE_RECOVER_CONFIRM = 1;
    public const TYPE_EVENT_CREATED_NOTIFICATION = 2;
    public const TYPE_EVENT_PUBLIC_NOTIFICATION = 3;

    /**
     * @var string
     *
     * @ORM\Column(type="guid")
     */
    private $identifier;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $link;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $sentDateTime;

    /**
     * @var User
     *
     * @ORM\ManyToOne (targetEntity="App\Entity\User")
     */
    private $sentBy;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $readAt;

    public static function create(int $emailType, User $sentBy, ?string $link)
    {
        $email = new Email();

        $email->identifier = UuidV4::v4();
        $email->type = $emailType;
        $email->link = $link;
        $email->sentBy = $sentBy;
        $email->sentDateTime = new \DateTime();

        return $email;
    }

    public function markRead()
    {
        $this->readAt = new \DateTime();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getSentDateTime(): DateTime
    {
        return $this->sentDateTime;
    }

    public function getSentBy(): User
    {
        return $this->sentBy;
    }

    public function getReadAt(): ?DateTime
    {
        return $this->readAt;
    }

    public function getContext(): array
    {
        return ['sentBy' => $this->sentBy, 'identifier' => $this->identifier, 'emailType' => $this->type];
    }
}
