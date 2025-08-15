<?php

declare(strict_types=1);

namespace ShopifyGraphQL\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ShopifyGraphQL\GraphQLResponse;
use ShopifyGraphQL\ShopifyGraphQLClient;

/**
 * Basic test suite for ShopifyGraphQLClient
 */
class ShopifyGraphQLClientTest extends TestCase
{
    private $httpClient;
    private $requestFactory;
    private $streamFactory;
    private $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);

        $this->client = new ShopifyGraphQLClient(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            'test-store.myshopify.com',
            'test-token'
        );
    }

    public function testClientInstantiation(): void
    {
        $this->assertInstanceOf(ShopifyGraphQLClient::class, $this->client);
    }

    public function testShopDomainSanitization(): void
    {
        // Test with .myshopify.com suffix
        $client1 = new ShopifyGraphQLClient(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            'test-store.myshopify.com',
            'token'
        );
        $this->assertInstanceOf(ShopifyGraphQLClient::class, $client1);

        // Test without suffix (should auto-append)
        $client2 = new ShopifyGraphQLClient(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            'test-store',
            'token'
        );
        $this->assertInstanceOf(ShopifyGraphQLClient::class, $client2);
    }

    public function testInvalidShopDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ShopifyGraphQLClient(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            'invalid.domain.com',
            'token'
        );
    }

    public function testSuccessfulQuery(): void
    {
        // Mock response
        $responseData = [
            'data' => [
                'shop' => [
                    'name' => 'Test Shop',
                    'id' => 'gid://shopify/Shop/1'
                ]
            ]
        ];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn(json_encode($responseData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $response->method('getHeaders')->willReturn([]);
        $response->method('getHeaderLine')->willReturn('');

        $request = $this->createMock(RequestInterface::class);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withBody')->willReturnSelf();

        $this->requestFactory
            ->method('createRequest')
            ->willReturn($request);

        $this->streamFactory
            ->method('createStream')
            ->willReturn($stream);

        $this->httpClient
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->client->query('{ shop { name id } }');

        $this->assertInstanceOf(GraphQLResponse::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('Test Shop', $result->get('shop.name'));
    }

    public function testClientFactory(): void
    {
        // This test would require HTTP discovery to be available
        // In a real test environment, you'd mock the discovery classes
        $this->markTestSkipped('Requires HTTP client discovery setup');

        // $client = ClientFactory::create('test-store', 'test-token');
        // $this->assertInstanceOf(ShopifyGraphQLClient::class, $client);
    }
}
