<?php

declare(strict_types=1);

namespace App\Services\RateProvider\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class RateProviderClientFactory
{
    public const MAX_ATTEMPTS = 3;

    public const RETRY_DELAY_MS = 100;

    public const DEFAULT_TIMEOUT_SEC = 5.0;

    public static function make(?HandlerStack $handlerStack = null): ClientInterface
    {
        $handlerStack ??= HandlerStack::create();

        $maxAttempts = (int) config('services.rate_provider.max_attempts', self::MAX_ATTEMPTS);
        $retryDelayMs = (int) config('services.rate_provider.retry_delay_ms', self::RETRY_DELAY_MS);

        $handlerStack->push(Middleware::retry(
            self::retryDecider($maxAttempts),
            self::retryDelay($retryDelayMs),
        ));

        return new Client([
            'base_uri' => rtrim((string) config('services.rate_provider.base_uri'), '/').'/',
            'timeout'  => config('services.rate_provider.timeout', self::DEFAULT_TIMEOUT_SEC),
            'handler'  => $handlerStack,
        ]);
    }

    private static function retryDecider(int $maxAttempts): callable
    {
        return static function (
            int $attempt,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?Throwable $exception = null,
        ) use ($maxAttempts): bool {
            if ($attempt >= $maxAttempts - 1) {
                return false;
            }

            if ($response !== null) {
                return $response->getStatusCode() >= 500 || $response->getStatusCode() === 429;
            }

            return $exception !== null;
        };
    }

    private static function retryDelay(int $retryDelayMs): callable
    {
        return static function (int $attempt, ?ResponseInterface $response = null) use ($retryDelayMs): int {
            if ($response !== null && $response->getStatusCode() === 429 && $response->hasHeader('Retry-After')) {
                return self::retryAfterMilliseconds($response->getHeaderLine('Retry-After')) ?? $retryDelayMs;
            }

            // Exponential backoff x2
            return $retryDelayMs * (2 ** $attempt);
        };
    }

    private static function retryAfterMilliseconds(string $retryAfter): ?int
    {
        if (is_numeric($retryAfter)) {
            return max(0, (int) $retryAfter) * 1000;
        }

        $timestamp = strtotime($retryAfter);

        if ($timestamp === false) {
            return null;
        }

        return max(0, $timestamp - time()) * 1000;
    }
}
