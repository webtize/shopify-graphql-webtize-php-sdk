<?php

declare(strict_types=1);

namespace ShopifyGraphQL;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;

/**
 * Factory for creating ShopifyGraphQLClient instances
 * 
 * Simplifies client creation with automatic HTTP client discovery
 */
class ClientFactory
{
    /**
     * Create a new ShopifyGraphQLClient with automatic HTTP client discovery
     *
     * @param string $shopDomain Your shop domain (e.g., 'yourstore' or 'yourstore.myshopify.com')
     * @param string $accessToken Your Shopify access token
     * @param array $options Additional configuration options
     * @return ShopifyGraphQLClient
     */
    public static function create(string $shopDomain, string $accessToken, array $options = []): ShopifyGraphQLClient
    {
        // Auto-discover HTTP client and factories
        $httpClient = $options['http_client'] ?? Psr18ClientDiscovery::find();
        $requestFactory = $options['request_factory'] ?? Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = $options['stream_factory'] ?? Psr17FactoryDiscovery::findStreamFactory();

        return new ShopifyGraphQLClient(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $shopDomain,
            $accessToken,
            $options
        );
    }

    /**
     * Create a new ShopifyGraphQLClient with custom HTTP components
     *
     * @param ClientInterface $httpClient PSR-18 HTTP client
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     * @param string $shopDomain Your shop domain
     * @param string $accessToken Your Shopify access token
     * @param array $options Additional configuration options
     * @return ShopifyGraphQLClient
     */
    public static function createWithHttpClient(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $shopDomain,
        string $accessToken,
        array $options = []
    ): ShopifyGraphQLClient {
        return new ShopifyGraphQLClient(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $shopDomain,
            $accessToken,
            $options
        );
    }
}
