<?php

declare(strict_types=1);

namespace ShopifyGraphQL;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ShopifyGraphQL\Exceptions\ShopifyGraphQLException;
use ShopifyGraphQL\Exceptions\AuthenticationException;
use ShopifyGraphQL\Exceptions\RateLimitException;

/**
 * Shopify GraphQL API Client
 * 
 * A modern PHP SDK for interacting with Shopify's GraphQL Admin API
 * 
 * @package ShopifyGraphQL
 * @author  Your Name
 * @license MIT
 */
class ShopifyGraphQLClient
{
    private const API_VERSION = '2025-07';
    private const ENDPOINT_PATH = '/admin/api/' . self::API_VERSION . '/graphql.json';

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private string $shopDomain;
    private string $accessToken;
    private array $defaultHeaders;
    private int $timeout;
    private int $maxRetries;

    /**
     * Initialize the Shopify GraphQL Client
     *
     * @param ClientInterface $httpClient PSR-18 HTTP client
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     * @param string $shopDomain Your shop domain (e.g., 'yourstore.myshopify.com')
     * @param string $accessToken Your Shopify access token
     * @param array $options Additional configuration options
     */
    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $shopDomain,
        string $accessToken,
        array $options = []
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->shopDomain = $this->sanitizeShopDomain($shopDomain);
        $this->accessToken = $accessToken;
        $this->timeout = $options['timeout'] ?? 30;
        $this->maxRetries = $options['max_retries'] ?? 3;

        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'X-Shopify-Access-Token' => $this->accessToken,
            'User-Agent' => 'ShopifyGraphQL-PHP-SDK/1.0.0',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Execute a GraphQL query
     *
     * @param string $query The GraphQL query string
     * @param array $variables Optional variables for the query
     * @param array $options Additional request options
     * @return GraphQLResponse
     * @throws ShopifyGraphQLException
     */
    public function query(string $query, array $variables = [], array $options = []): GraphQLResponse
    {
        return $this->execute($query, $variables, $options);
    }

    /**
     * Execute a GraphQL mutation
     *
     * @param string $mutation The GraphQL mutation string
     * @param array $variables Optional variables for the mutation
     * @param array $options Additional request options
     * @return GraphQLResponse
     * @throws ShopifyGraphQLException
     */
    public function mutate(string $mutation, array $variables = [], array $options = []): GraphQLResponse
    {
        return $this->execute($mutation, $variables, $options);
    }

    /**
     * Execute a raw GraphQL operation
     *
     * @param string $operation The GraphQL operation string
     * @param array $variables Optional variables for the operation
     * @param array $options Additional request options
     * @return GraphQLResponse
     * @throws ShopifyGraphQLException
     */
    public function execute(string $operation, array $variables = [], array $options = []): GraphQLResponse
    {
        $payload = [
            'query' => $operation,
        ];

        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            try {
                $request = $this->requestFactory
                    ->createRequest('POST', $this->buildUrl())
                    ->withBody($this->streamFactory->createStream(json_encode($payload)));

                foreach ($this->defaultHeaders as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }

                // Add custom headers from options
                if (isset($options['headers'])) {
                    foreach ($options['headers'] as $name => $value) {
                        $request = $request->withHeader($name, $value);
                    }
                }

                $response = $this->httpClient->sendRequest($request);

                return $this->handleResponse($response);

            } catch (RateLimitException $e) {
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                $retryAfter = $e->getRetryAfter();
                if ($retryAfter > 0 && $retryAfter <= 60) {
                    sleep($retryAfter);
                } else {
                    // Exponential backoff
                    sleep(min(pow(2, $attempt), 60));
                }

                $attempt++;
            }
        }

        throw new ShopifyGraphQLException('Max retries exceeded');
    }

    /**
     * Get shop information using a simple introspection query
     *
     * @return GraphQLResponse
     * @throws ShopifyGraphQLException
     */
    public function getShopInfo(): GraphQLResponse
    {
        $query = '
            query {
                shop {
                    id
                    name
                    myshopifyDomain
                    primaryDomain {
                        host
                    }
                    currencyCode
                    timezoneAbbreviation
                }
            }
        ';

        return $this->query($query);
    }

    /**
     * Get the current API rate limit status
     *
     * @return array
     */
    public function getRateLimitStatus(): array
    {
        // This would typically be populated after making a request
        // For now, return a placeholder structure
        return [
            'currently_available' => null,
            'maximum_available' => null,
            'restore_rate' => null,
        ];
    }

    /**
     * Build the complete API URL
     *
     * @return string
     */
    private function buildUrl(): string
    {
        return 'https://' . $this->shopDomain . self::ENDPOINT_PATH;
    }

    /**
     * Sanitize shop domain to ensure proper format
     *
     * @param string $domain
     * @return string
     */
    private function sanitizeShopDomain(string $domain): string
    {
        // Remove protocol if present
        $domain = preg_replace('#^https?://#', '', $domain);

        // Ensure .myshopify.com suffix if not present
        if (!str_ends_with($domain, '.myshopify.com')) {
            if (str_contains($domain, '.')) {
                throw new \InvalidArgumentException('Invalid shop domain format');
            }
            $domain .= '.myshopify.com';
        }

        return $domain;
    }

    /**
     * Handle HTTP response and convert to GraphQLResponse
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return GraphQLResponse
     * @throws ShopifyGraphQLException
     */
    private function handleResponse(\Psr\Http\Message\ResponseInterface $response): GraphQLResponse
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Handle different HTTP status codes
        switch ($statusCode) {
            case 401:
                throw new AuthenticationException('Invalid access token');
            case 403:
                throw new AuthenticationException('Access forbidden - check your permissions');
            case 423:
                throw new ShopifyGraphQLException('Shop is locked');
            case 429:
                $retryAfter = (int) $response->getHeaderLine('Retry-After');
                throw new RateLimitException('Rate limit exceeded', $retryAfter);
            case 500:
            case 502:
            case 503:
            case 504:
                throw new ShopifyGraphQLException('Shopify server error: ' . $statusCode);
        }

        if (empty($body)) {
            throw new ShopifyGraphQLException('Empty response from Shopify API');
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ShopifyGraphQLException('Invalid JSON response: ' . json_last_error_msg());
        }

        // Extract rate limit information from headers
        $rateLimitInfo = [
            'currently_available' => $this->parseRateLimitHeader($response, 'X-GraphQL-Cost-Include-Fields'),
            'maximum_available' => $this->parseRateLimitHeader($response, 'X-GraphQL-Cost-Throttle-Status'),
            'restore_rate' => $this->parseRateLimitHeader($response, 'X-GraphQL-Cost-Restore-Rate'),
        ];

        return new GraphQLResponse($data, $response->getHeaders(), $rateLimitInfo);
    }

    /**
     * Parse rate limit information from response headers
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $headerName
     * @return int|null
     */
    private function parseRateLimitHeader(\Psr\Http\Message\ResponseInterface $response, string $headerName): ?int
    {
        $headerValue = $response->getHeaderLine($headerName);
        if (empty($headerValue)) {
            return null;
        }

        // Parse complex header values like "40/1000"
        if (preg_match('/(\d+)(?:\/(\d+))?/', $headerValue, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
