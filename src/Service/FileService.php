<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\Delegation;
use App\Entity\Participant;
use App\Enum\ParticipantRole;
use App\Service\Interfaces\FileServiceInterface;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZipArchive;

class FileService implements FileServiceInterface
{
    /**
     * @var string
     */
    private $persistentDir;

    /**
     * @var string
     */
    private $transientDir;

    /**
     * @var SluggerInterface
     */
    private $slugger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * FileService constructor.
     */
    public function __construct(string $persistentDir, string $transientDir, SluggerInterface $slugger, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->persistentDir = $persistentDir;
        $this->transientDir = $transientDir;
        $this->slugger = $slugger;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    public function replacePortrait(Participant $participant, UploadedFile $file): bool
    {
        if (!$this->upload($participant, self::PORTRAIT, $file, $folder, $filename)) {
            return false;
        }

        // remove old
        if ($participant->getPortrait()) {
            unlink($folder.'/'.$participant->getPortrait());
        }

        $participant->setPortrait($filename);

        return true;
    }

    public function replacePapers(Participant $participant, UploadedFile $file): bool
    {
        if (!$this->upload($participant, self::PAPERS, $file, $folder, $filename)) {
            return false;
        }

        // remove old
        if ($participant->getPapers()) {
            unlink($folder.'/'.$participant->getPapers());
        }

        $participant->setPapers($filename);

        return true;
    }

    public function replaceConsent(Participant $participant, UploadedFile $file): bool
    {
        if (!$this->upload($participant, self::CONSENT, $file, $folder, $filename)) {
            return false;
        }

        // remove old
        if ($participant->getConsent()) {
            unlink($folder.'/'.$participant->getConsent());
        }

        $participant->setConsent($filename);

        return true;
    }

    public function removeFiles(Participant $participant)
    {
        if ($participant->getPortrait()) {
            $folder = $this->getOrCreateDelegationFolder(self::PORTRAIT, $participant->getDelegation());
            unlink($folder.'/'.$participant->getPortrait());
        }

        if ($participant->getPapers()) {
            $folder = $this->getOrCreateDelegationFolder(self::PAPERS, $participant->getDelegation());
            unlink($folder.'/'.$participant->getPapers());
        }

        if ($participant->getConsent()) {
            $folder = $this->getOrCreateDelegationFolder(self::CONSENT, $participant->getDelegation());
            unlink($folder.'/'.$participant->getConsent());
        }
    }

    public function downloadPortrait(Participant $participant, string $filename): Response
    {
        if ($participant->getPortrait() !== $filename) {
            throw new NotFoundHttpException();
        }

        return $this->download($participant, self::PORTRAIT, $filename, true);
    }

    public function downloadPapers(Participant $participant, string $filename): Response
    {
        if ($participant->getPapers() !== $filename) {
            throw new NotFoundHttpException();
        }

        return $this->download($participant, self::PAPERS, $filename, true);
    }

    public function downloadConsent(Participant $participant, string $filename): Response
    {
        if ($participant->getConsent() !== $filename) {
            throw new NotFoundHttpException();
        }

        return $this->download($participant, self::CONSENT, $filename, false);
    }

    public function downloadArchive(string $type): Response
    {
        if (!in_array($type, self::FILES)) {
            throw new NotFoundHttpException();
        }

        $folder = $this->getOrCreateFolder($type);
        $targetFile = $this->transientDir.'/'.$type.'.zip';

        $this->zipFolder($folder, $targetFile);

        $response = new BinaryFileResponse($targetFile);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    private function upload(Participant $participant, string $type, UploadedFile $file, ?string &$folder, ?string &$filename): bool
    {
        $folder = $this->getOrCreateDelegationFolder($type, $participant->getDelegation());

        $proposedFilename = $this->getFilename($type, $participant);
        $filename = sprintf('%s_%s.%s', $proposedFilename, uniqid(), $file->guessExtension());

        // Move the file to the directory where brochures are stored
        try {
            $file->move($folder, $filename);
        } catch (FileException $e) {
            $this->logger->error('failed to save file.'.$e->getMessage(), ['exception' => $e]);

            return false;
        }

        return true;
    }

    private function download(Participant $participant, string $type, string $filename, bool $inline): BinaryFileResponse
    {
        $folder = $this->getOrCreateDelegationFolder($type, $participant->getDelegation());
        $filePath = $folder.'/'.$filename;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition($inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $response;
    }

    private function getOrCreateFolder(string $type): string
    {
        $targetDir = $this->persistentDir.'/'.$type;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        return $targetDir;
    }

    private function getOrCreateDelegationFolder(string $type, Delegation $delegation): string
    {
        $targetDir = $this->getOrCreateFolder($type).'/'.$delegation->getName();
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        return $targetDir;
    }

    private function getFilename(string $type, Participant $participant): string
    {
        $parts = [
            $participant->getDelegation()->getName(),
            ParticipantRole::getTranslationForValue($participant->getRole(), $this->translator),
            $participant->getName(),
            $type,
        ];

        foreach ($parts as &$part) {
            $part = $this->slugger->slug($part);
        }

        return implode('_', $parts);
    }

    private function zipFolder(string $folder, string $targetPath)
    {
        // Get real path for our folder
        $rootPath = realpath($folder);

        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $empty = true;
        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
                $empty = false;
            }
        }

        if ($empty) {
            $zip->addFromString('.empty', 'no files found.');
        }

        // Zip archive will be created only after closing object
        $zip->close();
    }
}
