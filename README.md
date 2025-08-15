# Shopify GraphQL PHP SDK

A modern, PSR-compliant PHP SDK for interacting with Shopify's GraphQL Admin API. This SDK provides a clean, object-oriented interface with automatic HTTP client discovery, rate limiting, error handling, and convenient resource helpers.

## Features

- 🚀 **Modern PHP 8.1+** with strict typing and best practices
- 🔌 **PSR-18/17 Compliant** - Works with any HTTP client (Guzzle, Symfony, etc.)
- 🛡️ **Automatic Rate Limiting** with exponential backoff and retry logic  
- 🎯 **Type-Safe GraphQL** operations with query builder
- 📦 **Resource Helpers** for common operations (Products, Orders, etc.)
- ⚡ **Auto-Discovery** of HTTP clients and factories
- 🔍 **Comprehensive Error Handling** with specific exceptions
- 📖 **Extensive Documentation** and examples

## Installation

Install via Composer:

```bash
composer require webtize/shopify-graphql-sdk:@dev
```

For HTTP client support, also install one of the following:

```bash
# For Guzzle (recommended)
composer require php-http/guzzle7-adapter

# For Symfony HTTP Client  
composer require symfony/http-client php-http/httplug-bundle

# For cURL adapter
composer require php-http/curl-client php-http/message
```

## Quick Start

### Basic Setup

```php
use ShopifyGraphQL\ClientFactory;

// Simple setup with auto-discovery
$client = ClientFactory::create(
    'your-store.myshopify.com',  // or just 'your-store'
    'your-access-token-here'
);

// Get shop information
$response = $client->getShopInfo();
if ($response->isSuccessful()) {
    $shop = $response->get('shop');
    echo "Shop: " . $shop['name'];
}
```

### Using Resource Helpers

```php
use ShopifyGraphQL\Resources\Products;

$products = new Products($client);

// List products
$response = $products->list(10);
foreach ($response->get('products.edges', []) as $edge) {
    echo $edge['node']['title'] . "\n";
}

// Get single product
$product = $products->get('gid://shopify/Product/123');
echo $product->get('product.title');

// Create product
$newProduct = $products->create([
    'title' => 'New Product',
    'productType' => 'Electronics',
    'vendor' => 'ACME Corp'
]);
```

### Custom Queries with Query Builder

```php
use ShopifyGraphQL\QueryBuilder;

$query = QueryBuilder::query('GetCustomers')
    ->variable('first', 'Int!', 10)
    ->field('customers', function($builder) {
        $builder->field('edges', function($edge) {
            $edge->field('node', [
                'id', 'email', 'firstName', 'lastName'
            ]);
        });
    });

$response = $client->query($query->build(), ['first' => 5]);
```

## Configuration Options

```php
$client = ClientFactory::create(
    'your-store.myshopify.com',
    'your-access-token',
    [
        'timeout' => 30,           // Request timeout in seconds
        'max_retries' => 3,        // Max retry attempts for rate limits
        'headers' => [             // Additional headers
            'X-Custom-Header' => 'value'
        ]
    ]
);
```

## Advanced Usage

### Custom HTTP Client

```php
use ShopifyGraphQL\ClientFactory;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Nyholm\Psr7\Factory\Psr17Factory;

$httpClient = new GuzzleAdapter(new GuzzleClient([
    'timeout' => 60,
    'verify' => true
]));

$factory = new Psr17Factory();

$client = ClientFactory::createWithHttpClient(
    $httpClient,
    $factory,  // Request factory
    $factory,  // Stream factory  
    'your-store.myshopify.com',
    'your-access-token'
);
```

### Error Handling

```php
use ShopifyGraphQL\Exceptions\AuthenticationException;
use ShopifyGraphQL\Exceptions\RateLimitException;
use ShopifyGraphQL\Exceptions\ShopifyGraphQLException;

try {
    $response = $client->query($query);

} catch (AuthenticationException $e) {
    echo "Invalid credentials: " . $e->getMessage();

} catch (RateLimitException $e) {
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";

} catch (ShopifyGraphQLException $e) {
    echo "GraphQL Error: " . $e->getMessage();

    if ($e->hasGraphqlErrors()) {
        foreach ($e->getGraphqlErrors() as $error) {
            echo "- " . $error['message'] . "\n";
        }
    }
}
```

### Pagination

```php
$products = new Products($client);
$allProducts = [];
$cursor = null;

do {
    $response = $products->list(50, $cursor);

    if (!$response->isSuccessful()) {
        break;
    }

    $data = $response->get('products');
    $edges = $data['edges'] ?? [];

    foreach ($edges as $edge) {
        $allProducts[] = $edge['node'];
        $cursor = $edge['cursor'];
    }

    $hasNextPage = $data['pageInfo']['hasNextPage'] ?? false;

} while ($hasNextPage);
```

## Available Resources

Currently implemented resource helpers:

- `Products` - Product management operations

More resources coming soon:
- Orders
- Customers  
- Collections
- Inventory
- Fulfillments

## GraphQL Query Builder

The included query builder provides a fluent interface for constructing GraphQL queries:

```php
$query = QueryBuilder::query('GetProductsAndVariants')
    ->variable('productId', 'ID!')
    ->variable('first', 'Int', 10)
    ->field('product', function($builder) {
        $builder->field('id')
                ->field('title')
                ->field('variants', function($variantBuilder) {
                    $variantBuilder->field('edges', [
                        'node' => ['id', 'title', 'price']
                    ]);
                });
    })
    ->build();
```

## Rate Limiting

The SDK automatically handles Shopify's rate limiting:

- Detects 429 (rate limit) responses
- Implements exponential backoff retry strategy  
- Respects `Retry-After` headers
- Configurable max retry attempts

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer phpstan
```

Fix code style:

```bash
composer cs-fix
```

## Requirements

- PHP 8.1 or higher
- A PSR-18 HTTP client implementation
- Valid Shopify store and access token

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

- 📖 [Official Shopify GraphQL Documentation](https://shopify.dev/docs/api/admin-graphql)
- 🐛 [Issue Tracker](https://github.com/yourname/shopify-graphql-sdk/issues)
- 💬 [Discussions](https://github.com/yourname/shopify-graphql-sdk/discussions)

## Changelog

### v1.0.0
- Initial release
- Core GraphQL client with PSR-18 support
- Query builder with fluent interface
- Products resource helper
- Automatic rate limiting and retries
- Comprehensive error handling
# shopify-graphql-php-sdk
