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

use App\Helper\FileHelper;
use App\Service\Interfaces\StorageServiceInterface;
use DateTime;
use const DIRECTORY_SEPARATOR;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StorageService implements StorageServiceInterface
{
    public function uploadFile(UploadedFile $file, string $targetFolder): ?string
    {
        FileHelper::ensureFolderExists($targetFolder);
        $targetFileName = $this->getSanitizedUniqueFileName($targetFolder, $file->getClientOriginalName());
        if (!$file->move($targetFolder, $targetFileName)) {
            return null;
        }

        return $targetFolder.DIRECTORY_SEPARATOR.$targetFileName;
    }

    private function getSanitizedUniqueFileName(string $targetFolder, string $targetFileName): string
    {
        $fileName = pathinfo($targetFileName, PATHINFO_FILENAME);
        $extension = pathinfo($targetFileName, PATHINFO_EXTENSION);

        $sanitizedFileName = FileHelper::sanitizeFileName($fileName).'.'.$extension;
        $targetPath = $targetFolder.DIRECTORY_SEPARATOR.$sanitizedFileName;
        if (!is_file($targetPath)) {
            return $sanitizedFileName;
        }

        $now = new DateTime();
        $counter = 0;
        do {
            $prefix = $sanitizedFileName.'_duplicate_'.$now->format('Y-m-d\THi');
            if ($counter++ > 0) {
                $prefix .= '_'.$counter;
            }
            $uniqueFileName = $prefix.'.'.$extension;
            $uniqueTargetPath = $targetFolder.DIRECTORY_SEPARATOR.$uniqueFileName;
        } while (file_exists($uniqueTargetPath));

        return $uniqueFileName;
    }
}
