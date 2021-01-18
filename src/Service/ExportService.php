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

use App\Service\Interfaces\ExportServiceInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ExportService implements ExportServiceInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ExportService constructor.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function exportToCsv(array $elements, string $serializationGroup, string $fileSuffix): Response
    {
        $content = $this->serializer->serialize($elements, 'csv', ['groups' => $serializationGroup, AbstractObjectNormalizer::SKIP_NULL_VALUES => false]);

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($content) {
                echo $content;
            }
        );
        $response->setStatusCode(200);

        $filename = (new \DateTime())->format('c').' - '.$fileSuffix.'.csv';
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Disposition', $dispositionHeader);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

        return $response;
    }
}
