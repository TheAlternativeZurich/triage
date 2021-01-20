<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Traits;

use PHPUnit\Framework\Constraint\LogicalNot;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseIsRedirected;

trait AssertAuthenticationTrait
{
    private function assertNotAuthenticated(KernelBrowser $client)
    {
        $client->request('GET', '/events/mine');
        $this->assertResponseRedirects('/login');
    }

    private function assertAuthenticated(KernelBrowser $client)
    {
        $client->request('GET', '/events/mine');
        $constraint = new ResponseIsRedirected();
        $notRedirects = new LogicalNot($constraint);
        $this->assertThatForResponse($notRedirects);
    }
}
