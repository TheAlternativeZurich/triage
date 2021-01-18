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

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface StorageServiceInterface
{
    public function uploadFile(UploadedFile $file, string $targetFolder): ?string;
}
