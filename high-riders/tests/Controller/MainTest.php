<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MainTest extends WebTestCase
{
    public function testLogin(): void
    {
        // On simule l'accès à une la page /login
        // via un navigateur intégré
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Est ce que la page répond correctement
        $this->assertResponseIsSuccessful();

        // On vérifie également que la page de login possède bien
        // une balise h1 avec le contenu "Please sign in"
        // $this->assertSelectorTextContains('h1.h3', 'Please sign in');
    }

    // public function testRegister(): void
    // {
    //     // On simule l'accès à une la page /login
    //     // via un navigateur intégré
    //     $client = static::createClient();
    //     $crawler = $client->request('GET', '/');

    //     // Est ce que la page répond correctement
    //     $this->assertResponseIsSuccessful();

    //     // On vérifie également que la page de login possède bien
    //     // une balise h1 avec le contenu "Please sign in"
    //     // $this->assertSelectorTextContains('h1.h3', 'Please sign in');
    // }
}
