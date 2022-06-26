<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Traits\AssertAuthenticationTrait;
use App\Tests\Traits\AssertEmailTrait;
use Doctrine\Persistence\ManagerRegistry;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    use AssertEmailTrait;
    use AssertAuthenticationTrait;

    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    protected $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function testCanSetup()
    {
        $client = $this->client;
        $this->databaseTool->loadFixtures();

        $email = 'f@mangel.io';
        $password = 'asdf1234';

        $this->assertNotAuthenticated($client);

        $this->assertCanSetup($client, $email, $password);
        $this->assertAuthenticated($client);

        $this->assertCanLogout($client);
        $this->assertNotAuthenticated($client);

        $this->assertCanNotSetup($client);
    }

    public function testCanRegister()
    {
        $client = $this->client;
        $this->databaseTool->loadAllFixtures();

        $email = 'f@mangel.io';
        $password = 'asdf1234';

        $this->assertNotAuthenticated($client);

        $this->register($client, $email, $password);
        $this->assertNotAuthenticated($client);

        $this->registerConfirm($client, $email);
        $this->assertAuthenticated($client);

        $this->assertCanLogout($client);
        $this->assertNotAuthenticated($client);

        $this->assertCanLogin($client, $email, $password);
        $this->assertAuthenticated($client);

        $this->assertCanLogout($client);
        $this->assertNotAuthenticated($client);

        $this->assertCanRecover($client, $email);
        $this->assertNotAuthenticated($client);

        $authenticationHash = $this->getAuthenticationHash($email);
        $this->assertCanRecoverConfirm($client, $authenticationHash, $password);
        $this->assertAuthenticated($client);
    }

    private function register(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/register');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('register[submit]')->form();
        $form['register[profile][email]'] = $email;
        $form['register[password][plainPassword]'] = $password;
        $form['register[password][repeatPlainPassword]'] = $password;

        $client->submit($form);
        $this->assertResponseRedirects('/login');

        $authenticationHash = $this->getAuthenticationHash($email);
        $this->assertSingleEmailSentWithBodyContains($authenticationHash);

        $client->followRedirect();
        $this->assertStringContainsString('sent you', $client->getResponse()->getContent()); // alert to user
    }

    private function registerConfirm(KernelBrowser $client, string $email): void
    {
        $authenticationHash = $this->getAuthenticationHash($email);

        $client->request('GET', '/register/confirm/'.$authenticationHash);
        $this->assertResponseRedirects();

        $client->followRedirect();
        $this->assertStringContainsString('confirm', $client->getResponse()->getContent()); // alert to user
    }

    private function assertCanLogin(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/login');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('login_submit')->form();
        $form['login[email]'] = $email;
        $form['login[password]'] = $password;

        $client->submit($form);
        $this->assertResponseRedirects();
    }

    private function assertCanLogout(KernelBrowser $client): void
    {
        $client->request('GET', '/logout');
        $this->assertResponseRedirects();
    }

    private function assertCanSetup(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/setup');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('register_submit')->form();
        $form['register[profile][email]'] = $email;
        $form['register[password][plainPassword]'] = $password;
        $form['register[password][repeatPlainPassword]'] = $password;

        $client->submit($form);
        $this->assertResponseRedirects('/');

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Welcome', $client->getResponse()->getContent()); // alert to user
    }

    private function assertCanNotSetup(KernelBrowser $client): void
    {
        $client->request('GET', '/setup');
        $this->assertResponseRedirects();
    }

    private function assertCanRecover(KernelBrowser $client, string $email): void
    {
        $crawler = $client->request('GET', '/recover');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('only_email_submit')->form();
        $form['only_email[email]'] = $email;

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('sent', $client->getResponse()->getContent()); // alert to user

        $authenticationHash = $this->getAuthenticationHash($email);
        $this->assertSingleEmailSentWithBodyContains($authenticationHash);
    }

    private function assertCanRecoverConfirm(KernelBrowser $client, string $authenticationHash, string $password): void
    {
        $crawler = $client->request('GET', '/recover/confirm/'.$authenticationHash);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('set_password_submit')->form();
        $form['set_password[plainPassword]'] = $password;
        $form['set_password[repeatPlainPassword]'] = $password;
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertStringContainsString('set', $client->getResponse()->getContent()); // alert to user
    }

    private function getAuthenticationHash(string $email)
    {
        $registry = static::$container->get(ManagerRegistry::class);
        $repository = $registry->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy(['email' => $email]);

        return $user->getAuthenticationHash();
    }
}
