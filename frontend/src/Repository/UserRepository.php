<?php

namespace App\Repository;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class UserRepository
{
    private const CACHE_KEY_LIST = 'users_list_all';
    private const CACHE_KEY_INDEX = 'users_cache_keys_index';
    private const CACHE_TTL = 3600;

    private string $apiUrl;

    public function __construct(
        private HttpClientInterface $client,
        private string $phoenixApiUrl,
        private CacheItemPoolInterface $cache,
        private LoggerInterface $logger
    ) {
        $this->apiUrl = $phoenixApiUrl;
    }

    private function getResponseData(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
             return [];
        }

        try {
            $data = $response->toArray();
            return $data['data'] ?? $data ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Error parsing JSON from Phoenix API: ' . $e->getMessage());
            return [];
        }
    }

    public function getUsers(array $queryParams = []): array
    {
        $cacheKey = self::CACHE_KEY_LIST . '_' . md5(serialize($queryParams));
        
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        try {
            $response = $this->client->request('GET', $this->apiUrl . '/users', [
                'query' => $queryParams
            ]);
            $users = $this->getResponseData($response);

            if (!empty($users)) {
                $cacheItem->set($users);
                $cacheItem->expiresAfter(self::CACHE_TTL);
                $this->cache->save($cacheItem);

                $indexItem = $this->cache->getItem(self::CACHE_KEY_INDEX);
                $keys = $indexItem->isHit() ? (array)$indexItem->get() : [];
                $keys[] = $cacheKey;
                $keys = array_values(array_unique($keys));
                $indexItem->set($keys);
                $indexItem->expiresAfter(self::CACHE_TTL * 24);
                $this->cache->save($indexItem);
            }

            return $users;

        } catch (ExceptionInterface $e) {
            $this->logger->error("Phoenix API error: " . $e->getMessage() . ". Trying to retrieve from cache.");

            $fallbackCacheKey = self::CACHE_KEY_LIST . '_' . md5(serialize([]));
            $fallbackCacheItem = $this->cache->getItem($fallbackCacheKey);

            if ($fallbackCacheItem->isHit()) {
                return $fallbackCacheItem->get();
            }

            throw $e;
        }
    }

    public function getUser(int $id): array
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl . '/users/' . $id);
            return $this->getResponseData($response);
        } catch (ExceptionInterface $e) {
            $this->logger->error("Error fetching user {$id} from Phoenix API: " . $e->getMessage());
            return [];
        }
    }

    private function invalidateCache(): void
    {
        $indexItem = $this->cache->getItem(self::CACHE_KEY_INDEX);

        if (!$indexItem->isHit()) {
            return;
        }

        $keys = (array) $indexItem->get();

        if (!empty($keys)) {
            try {
                $this->cache->deleteItems($keys);
            } catch (\Throwable $e) {
                $this->logger->warning('deleteItems failed, attempting single deletions: ' . $e->getMessage());
                foreach ($keys as $k) {
                    try {
                        $this->cache->deleteItem($k);
                    } catch (\Throwable $inner) {
                        $this->logger->error("Failed to delete cache item {$k}: " . $inner->getMessage());
                    }
                }
            }
        }

        try {
            $this->cache->deleteItem(self::CACHE_KEY_INDEX);
        } catch (\Throwable $e) {
            $this->logger->error("Failed to delete cache index: " . $e->getMessage());
        }
    }

    public function createUser(array $data): void
    {
        try {
            $response = $this->client->request('POST', $this->apiUrl . '/users', ['json' => ['user' => $data]]);
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                $this->invalidateCache();
            } else {
                $this->logger->warning("Create user returned status {$status}");
            }
        } catch (ExceptionInterface $e) {
            $this->logger->error('Create user failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateUser(int $id, array $data): void
    {
        try {
            $response = $this->client->request('PUT', $this->apiUrl . '/users/' . $id, ['json' => ['user' => $data]]);
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                $this->invalidateCache();
            } else {
                $this->logger->warning("Update user {$id} returned status {$status}");
            }
        } catch (ExceptionInterface $e) {
            $this->logger->error("Update user {$id} failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteUser(int $id): void
    {
        try {
            $response = $this->client->request('DELETE', $this->apiUrl . '/users/' . $id);
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                $this->invalidateCache();
            } else {
                $this->logger->warning("Delete user {$id} returned status {$status}");
            }
        } catch (ExceptionInterface $e) {
            $this->logger->error("Delete user {$id} failed: " . $e->getMessage());
            throw $e;
        }
    }
}
