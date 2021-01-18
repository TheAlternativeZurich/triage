<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Controller\Base\BaseController;
use App\Entity\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/email")
 */
class EmailController extends BaseController
{
    /**
     * @Route("/{identifier}", name="email")
     *
     * @return Response
     */
    public function emailAction(Email $email)
    {
        return $this->render('email/_view_online_base.html.twig', $email->getContext());
    }
}
