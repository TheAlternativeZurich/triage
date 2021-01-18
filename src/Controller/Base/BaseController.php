<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Base;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BaseController extends AbstractController
{
    /**
     * @return User|null
     */
    protected function getUser()
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::getUser();
    }

    public static function getSubscribedServices()
    {
        return parent::getSubscribedServices() + ['session' => '?'.SessionInterface::class];
    }

    protected function displayError(string $message, string $link = null)
    {
        $this->displayFlash('danger', $message, $link);
    }

    protected function displaySuccess(string $message, string $link = null)
    {
        $this->displayFlash('success', $message, $link);
    }

    protected function displayDanger(string $message, string $link = null)
    {
        $this->displayFlash('danger', $message, $link);
    }

    protected function displayWarning(string $message, string $link = null)
    {
        $this->displayFlash('warning', $message, $link);
    }

    protected function displayInfo(string $message, string $link = null)
    {
        $this->displayFlash('info', $message, $link);
    }

    private function displayFlash(string $type, string $message, string $link = null)
    {
        if (null !== $link) {
            $message = '<a href="'.$link.'">'.$message.'</a>';
        }
        $this->get('session')->getFlashBag()->add($type, $message);
    }
}
