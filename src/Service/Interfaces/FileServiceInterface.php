<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Interfaces;

use App\Entity\Participant;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

interface FileServiceInterface
{
    public const PORTRAIT = 'portrait';
    public const PAPERS = 'papers';
    public const CONSENT = 'consent';

    public const FILES = [self::PORTRAIT, self::PAPERS, self::CONSENT];

    public function replacePortrait(Participant $participant, UploadedFile $file): bool;

    public function replacePapers(Participant $participant, UploadedFile $file): bool;

    public function replaceConsent(Participant $participant, UploadedFile $file): bool;

    public function removeFiles(Participant $participant);

    public function downloadPortrait(Participant $participant, string $filename): Response;

    public function downloadPapers(Participant $participant, string $filename): Response;

    public function downloadConsent(Participant $participant, string $filename): Response;

    public function downloadArchive(string $type): Response;
}
