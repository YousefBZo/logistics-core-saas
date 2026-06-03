<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key'));

        if ($idempotencyKey === '') {
            return response()->json([
                'message' => 'Missing required X-Idempotency-Key header.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($idempotencyKey) > 120) {
            return response()->json([
                'message' => 'The X-Idempotency-Key header may not exceed 120 characters.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var Repository $cache */
        $cache = Cache::store(config('idempotency.store', 'redis'));
        $requestHash = hash('sha256', $request->getContent());
        $fingerprint = $this->cacheFingerprint($request, $idempotencyKey);
        $responseKey = 'idempotency:response:'.$fingerprint;
        $lock = $this->resolveLockProvider($cache)->lock(
            'idempotency:lock:'.$fingerprint,
            (int) config('idempotency.lock_seconds', 30)
        );

        if ($cachedResponse = $cache->get($responseKey)) {
            return $this->cachedResponse($cachedResponse, $requestHash);
        }

        if (! $lock->get()) {
            return response()->json([
                'message' => 'A request with this idempotency key is already being processed.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            if ($cachedResponse = $cache->get($responseKey)) {
                return $this->cachedResponse($cachedResponse, $requestHash);
            }

            $response = $next($request);
            $response->headers->set('X-Idempotency-Cache', 'MISS');

            if ($response->getStatusCode() < 500) {
                $cache->put($responseKey, [
                    'request_hash' => $requestHash,
                    'status' => $response->getStatusCode(),
                    'headers' => $this->cacheableHeaders($response),
                    'body' => $response->getContent(),
                ], (int) config('idempotency.ttl_seconds', 60));
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    private function resolveLockProvider(Repository $cache): LockProvider
    {
        $store = $cache->getStore();

        if (! $store instanceof LockProvider) {
            throw new \RuntimeException('Configured idempotency cache store does not support atomic locks.');
        }

        return $store;
    }

    private function cacheFingerprint(Request $request, string $idempotencyKey): string
    {
        $user = $request->user();

        return hash('sha256', implode('|', [
            $user === null ? 'guest' : (string) $user->tenant_id,
            $user === null ? 'guest' : (string) $user->id,
            $request->method(),
            $request->path(),
            $idempotencyKey,
        ]));
    }

    /**
     * @param  array{request_hash: string, status: int, headers: array<string, string>, body: string}  $cachedResponse
     */
    private function cachedResponse(array $cachedResponse, string $requestHash): Response
    {
        if ($cachedResponse['request_hash'] !== $requestHash) {
            return response()->json([
                'message' => 'This idempotency key has already been used with a different request payload.',
            ], Response::HTTP_CONFLICT);
        }

        return response($cachedResponse['body'], $cachedResponse['status'], $cachedResponse['headers'])
            ->header('X-Idempotency-Cache', 'HIT');
    }

    /**
     * @return array<string, string>
     */
    private function cacheableHeaders(Response $response): array
    {
        if (! $response->headers->has('Content-Type')) {
            return [];
        }

        return [
            'Content-Type' => (string) $response->headers->get('Content-Type'),
        ];
    }
}
