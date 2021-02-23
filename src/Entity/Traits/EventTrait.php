<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait EventTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $parts;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $author;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getParts(): ?int
    {
        return $this->parts;
    }

    public function setParts(?int $parts): void
    {
        $this->parts = $parts;
    }
}
