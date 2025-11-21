<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PhoenixApiService
{
    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $client,
        private string $phoenixApiUrl
    ) {
        $this->apiUrl = $phoenixApiUrl;
    }
    
    private function getResponseData(ResponseInterface $response): array
    {
        $response->getStatusCode();
        
        try {
            return $response->toArray()['data'] ?? $response->toArray() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getUsers(array $queryParams = []): array
    {
        $response = $this->client->request('GET', $this->apiUrl . '/users', [
            'query' => $queryParams
        ]);
        return $this->getResponseData($response);
    }

    public function getUser(int $id): array
    {
        $response = $this->client->request('GET', $this->apiUrl . '/users/' . $id);
        return $this->getResponseData($response);
    }

    public function createUser(array $data): void
    {
        $response = $this->client->request('POST', $this->apiUrl . '/users', ['json' => ['user' => $data]]);
        $response->getStatusCode();
    }

    public function updateUser(int $id, array $data): void
    {
        $response = $this->client->request('PUT', $this->apiUrl . '/users/' . $id, ['json' => ['user' => $data]]);
        $response->getStatusCode();
    }

    public function deleteUser(int $id): void
    {
        $response = $this->client->request('DELETE', $this->apiUrl . '/users/' . $id);
        $response->getStatusCode(); 
    }
    
    public function importUsers(): void
    {
        $response = $this->client->request('POST', $this->apiUrl . '/import');
        $response->getStatusCode();
    }
}