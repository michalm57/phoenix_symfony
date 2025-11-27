<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserE2ETest extends WebTestCase
{
    public function testFullUserLifecycle(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $initialUserCount = $crawler->filter('tbody tr')->count();
        $this->assertGreaterThanOrEqual(1, $initialUserCount);

        $uniqueSuffix = uniqid();
        $newName = 'Test';
        $newSurname = 'User' . $uniqueSuffix;
        $fullName = $newName . ' ' . $newSurname;

        $crawler = $client->request('GET', '/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save')->form([
            'user[first_name]' => $newName,
            'user[last_name]' => $newSurname,
            'user[gender]' => 'male',
            'user[birthdate]' => '2000-01-01',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertUserVisible($client, $fullName, true);

        $crawler = $client->request('GET', '/');
        $newUserId = null;
        $crawler->filter('tbody tr')->each(function ($row) use (&$newUserId, $fullName) {
            if (str_contains($row->text(), $fullName)) {
                $editLink = $row->selectLink('Edit')->attr('href');
                if (preg_match('/\/(\d+)\/edit/', $editLink, $matches)) {
                    $newUserId = $matches[1];
                }
            }
        });
        $this->assertNotNull($newUserId);

        $crawler = $client->request('GET', '/' . $newUserId . '/edit');
        $this->assertResponseIsSuccessful();

        $changedFullName = $newName . ' (Changed) ' . $newSurname;

        $form = $crawler->selectButton('Save')->form([
            'user[first_name]' => $newName . ' (Changed)',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertUserVisible($client, $changedFullName, true);

        $client->request('POST', '/' . $newUserId, ['_method' => 'DELETE']);
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertUserVisible($client, $changedFullName, false);
    }

    private function assertUserVisible($client, string $fullName, bool $expected): void
    {
        $crawler = $client->request('GET', '/');
        $found = false;

        $crawler->filter('tbody tr')->each(function ($row) use (&$found, $fullName) {
            $columns = $row->filter('td');
            if ($columns->count() >= 1) {
                $nameColumn = trim($columns->eq(0)->text());
                if ($nameColumn === $fullName) {
                    $found = true;
                }
            }
        });

        $this->assertSame($expected, $found);
    }
}
