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

use App\Service\Configuration\TriagePurpose;
use App\Service\Interfaces\ConfigurationServiceInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigurationService implements ConfigurationServiceInterface
{
    /**
     * @var string
     */
    private $assetsDir;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(string $assetsDir, SerializerInterface $serializer)
    {
        $this->assetsDir = $assetsDir;
        $this->serializer = $serializer;
    }

    public function getTriagePurpose(): TriagePurpose
    {
        $triagePurposeJson = file_get_contents($this->assetsDir.'/configuration/triage_purpose.json');

        return $this->serializer->deserialize($triagePurposeJson, TriagePurpose::class, 'json');
    }
}
