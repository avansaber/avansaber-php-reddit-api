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
     * Generate PKCE verifier and S256 challenge per RFC 7636.
     *
     * @return array{verifier:string,challenge:string}
     */
    public function generatePkcePair(int $verifierLength = 64): array
    {
        $length = max(43, min(128, $verifierLength));
        $random = random_bytes($length);
        $verifier = $this->base64UrlEncode($random);
        $challenge = $this->base64UrlEncode(hash('sha256', $verifier, true));
        return [
            'verifier' => $verifier,
            'challenge' => $challenge,
        ];
    }

    /**
     * Build the OAuth2 authorization URL.
     * Include PKCE params when $codeChallenge provided.
     *
     * @param array<int,string> $scopes
     */
    public function getAuthUrl(
        string $clientId,
        string $redirectUri,
        array $scopes,
        string $state,
        ?string $codeChallenge = null
    ): string {
        $params = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'duration' => 'permanent',
            'scope' => implode(' ', $scopes),
        ];

        if ($codeChallenge !== null && $codeChallenge !== '') {
            $params['code_challenge_method'] = 'S256';
            $params['code_challenge'] = $codeChallenge;
        }

        return 'https://www.reddit.com/api/v1/authorize?' . http_build_query($params);
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
     * Exchange an authorization code for tokens. Supports PKCE when $codeVerifier is provided.
     * If $clientSecret is null, an empty secret is used in Basic auth (installed app + PKCE).
     *
     * @return array<string,mixed>
     */
    public function getAccessTokenFromCode(
        string $clientId,
        ?string $clientSecret,
        string $code,
        string $redirectUri,
        ?string $codeVerifier = null
    ): array {
        $endpoint = 'https://www.reddit.com/api/v1/access_token';

        $bodyParams = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];
        if ($codeVerifier !== null && $codeVerifier !== '') {
            $bodyParams['code_verifier'] = $codeVerifier;
        }

        $bodyString = http_build_query($bodyParams);

        $basic = base64_encode($clientId . ':' . ($clientSecret ?? ''));

        $request = $this->requestFactory
            ->createRequest('POST', $endpoint)
            ->withHeader('User-Agent', $this->config->getUserAgent())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Authorization', 'Basic ' . $basic);

        $request = $request->withBody($this->streamFactory->createStream($bodyString));

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Transport error during auth code exchange: ' . $e->getMessage(), 0, null, $e);
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        if ($status !== 200) {
            throw new AuthenticationException('Reddit token exchange returned HTTP ' . $status, $status, $raw);
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['access_token'])) {
            throw new AuthenticationException('Invalid authorization code response', $status, $raw);
        }

        return $data;
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

    private function base64UrlEncode(string $bytes): string
    {
        $b64 = base64_encode($bytes);
        $url = strtr($b64, '+/', '-_');
        return rtrim($url, '=');
    }
}