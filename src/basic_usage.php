<?php

namespace ShopifyGraphQL;
require_once __DIR__ . '/../vendor/autoload.php';

use ShopifyGraphQL\ClientFactory;
use ShopifyGraphQL\Products;
use ShopifyGraphQL\QueryBuilder;

// Initialize the client
$client = ClientFactory::create(
    'your-store.myshopify.com',  // or just 'your-store'
    'your-access-token-here'
);

try {
    // Example 1: Get shop information
    echo "=== Shop Information ===\n";
    $shopResponse = $client->getShopInfo();
    if ($shopResponse->isSuccessful()) {
        $shop = $shopResponse->get('shop');
        echo "Shop Name: " . $shop['name'] . "\n";
        echo "Domain: " . $shop['myshopifyDomain'] . "\n";
        echo "Currency: " . $shop['currencyCode'] . "\n";
    }

    // Example 2: List products using resource helper
    echo "\n=== Products (using resource helper) ===\n";
    $products = new Products($client);
    $productResponse = $products->list(5); // Get first 5 products

    if ($productResponse->isSuccessful()) {
        $productEdges = $productResponse->get('products.edges', []);
        foreach ($productEdges as $edge) {
            $product = $edge['node'];
            echo "- {$product['title']} (ID: {$product['id']})\n";
        }
    }

    // Example 3: Custom query using QueryBuilder
    echo "\n=== Custom Query (using QueryBuilder) ===\n";
    $query = QueryBuilder::query('GetCustomerCount')
        ->field('customers', function($builder) {
            $builder->field('edges', function($edgeBuilder) {
                $edgeBuilder->field('node', ['id', 'email']);
            });
        });

    $customResponse = $client->query($query->build());
    if ($customResponse->isSuccessful()) {
        $customerCount = count($customResponse->get('customers.edges', []));
        echo "Total customers fetched: {$customerCount}\n";
    }

    // Example 4: Handle pagination
    echo "\n=== Pagination Example ===\n";
    $allProducts = [];
    $hasNextPage = true;
    $cursor = null;

    while ($hasNextPage && count($allProducts) < 20) { // Limit to 20 for example
        $response = $products->list(5, $cursor);
        if ($response->isSuccessful()) {
            $data = $response->get('products');
            $edges = $data['edges'] ?? [];

            foreach ($edges as $edge) {
                $allProducts[] = $edge['node'];
                $cursor = $edge['cursor'];
            }

            $hasNextPage = $data['pageInfo']['hasNextPage'] ?? false;
            echo "Fetched " . count($edges) . " products, total: " . count($allProducts) . "\n";
        } else {
            break;
        }
    }

    // Example 5: Create a product
    echo "\n=== Create Product ===\n";
    $newProduct = [
        'title' => 'Test Product from SDK',
        'productType' => 'Test',
        'vendor' => 'SDK Vendor',
        'status' => 'DRAFT'
    ];

    $createResponse = $products->create($newProduct);
    if ($createResponse->isSuccessful()) {
        $product = $createResponse->get('productCreate.product');
        echo "Created product: {$product['title']} (ID: {$product['id']})\n";
    } else {
        $errors = $createResponse->get('productCreate.userErrors', []);
        foreach ($errors as $error) {
            echo "Error: {$error['message']}\n";
        }
    }

} catch (ShopifyGraphQL\AuthenticationException $e) {
    echo "Authentication error: " . $e->getMessage() . "\n";
} catch (ShopifyGraphQL\RateLimitException $e) {
    echo "Rate limit exceeded. Retry after: " . $e->getRetryAfter() . " seconds\n";
} catch (ShopifyGraphQL\ShopifyGraphQLException $e) {
    echo "Shopify API error: " . $e->getMessage() . "\n";
    if ($e->hasGraphqlErrors()) {
        echo "GraphQL errors: " . json_encode($e->getGraphqlErrors()) . "\n";
    }
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
