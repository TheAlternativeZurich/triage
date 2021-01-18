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

use App\Entity\User;
use App\Tests\DataFixtures\TestUserFixtures;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthenticationTrait
{
    private function loginAdminUser(KernelBrowser $client): User
    {
        return $this->loginUser($client, TestUserFixtures::USER_ADMIN_EMAIL);
    }

    private function loginDelegationUser(KernelBrowser $client): User
    {
        return $this->loginUser($client, TestUserFixtures::USER_DELEGATION_EMAIL);
    }

    private function loginUser(KernelBrowser $client, string $email): User
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::$container->get(ManagerRegistry::class);
        $userRepository = $managerRegistry->getRepository(User::class);
        $testUser = $userRepository->findOneBy(['email' => $email]);

        if (!$testUser) {
            throw new \Exception('User for E-Mail '.$email.' not found. Likely you need to load the '.TestUserFixtures::class.' fixture first.');
        }

        $client->loginUser($testUser);

        return $testUser;
    }
}
