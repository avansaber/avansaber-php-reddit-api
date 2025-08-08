<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Auth;

use Avansaber\RedditApi\Config\Config;
use Avansaber\RedditApi\Exceptions\AuthenticationException;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class Auth
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly Config $config,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Application-only (client credentials) flow.
     * Returns an access token string.
     *
     * @param array<int, string> $scopes
     */
    public function appOnly(string $clientId, string $clientSecret, array $scopes = ['read', 'identity']): string
    {
        $endpoint = 'https://www.reddit.com/api/v1/access_token';

        $bodyParams = [
            'grant_type' => 'client_credentials',
        ];
        if (!empty($scopes)) {
            $bodyParams['scope'] = implode(' ', $scopes);
        }

        $bodyString = http_build_query($bodyParams);

        $request = $this->requestFactory
            ->createRequest('POST', $endpoint)
            ->withHeader('User-Agent', $this->config->getUserAgent())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Authorization', 'Basic ' . base64_encode($clientId . ':' . $clientSecret));

        $request = $request->withBody($this->streamFactory->createStream($bodyString));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Transport error during token request: ' . $e->getMessage(), 0, null, $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status !== 200) {
            throw new AuthenticationException('Reddit token endpoint returned HTTP ' . $status, $status, $raw);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new AuthenticationException('Invalid JSON from token endpoint', $status, $raw);
        }

        $token = $data['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new AuthenticationException('No access_token in token response', $status, $raw);
        }

        return $token;
    }

    /**
     * Refresh access token using a refresh_token.
     * Returns decoded JSON as array, including new access_token and optionally refresh_token.
     *
     * @return array<string,mixed>
     */
    public function refreshAccessToken(string $clientId, string $clientSecret, string $refreshToken): array
    {
        $endpoint = 'https://www.reddit.com/api/v1/access_token';

        $bodyParams = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        $bodyString = http_build_query($bodyParams);

        $request = $this->requestFactory
            ->createRequest('POST', $endpoint)
            ->withHeader('User-Agent', $this->config->getUserAgent())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Authorization', 'Basic ' . base64_encode($clientId . ':' . $clientSecret));

        $request = $request->withBody($this->streamFactory->createStream($bodyString));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Transport error during token refresh: ' . $e->getMessage(), 0, null, $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        if ($status !== 200) {
            throw new AuthenticationException('Reddit token refresh returned HTTP ' . $status, $status, $raw);
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['access_token'])) {
            throw new AuthenticationException('Invalid refresh response', $status, $raw);
        }

        return $data;
    }
}