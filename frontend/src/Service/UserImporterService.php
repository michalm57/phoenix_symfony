<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserImporterService
{
    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $client,
        private string $phoenixApiUrl
    ) {
        $this->apiUrl = $phoenixApiUrl;
    }

    public function importUsers(): void
    {
        $this->client->request('POST', $this->apiUrl . '/import')->getStatusCode();
    }
}