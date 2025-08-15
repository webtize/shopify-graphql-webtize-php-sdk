<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ShopifyGraphQL\ClientFactory;
use ShopifyGraphQL\QueryBuilder;

/**
 * Advanced usage examples demonstrating complex GraphQL operations
 */

// Initialize client with custom configuration
$client = ClientFactory::create(
    'your-store',
    'your-access-token',
    [
        'timeout' => 60,
        'max_retries' => 5,
        'headers' => [
            'X-Shopify-Storefront-Access-Token' => 'additional-token'
        ]
    ]
);

try {
    // Advanced Example 1: Complex product query with variants and metafields
    echo "=== Complex Product Query ===\n";

    $complexQuery = QueryBuilder::query('GetProductsWithVariants')
        ->variable('first', 'Int!', 10)
        ->variable('includeVariants', 'Boolean!', true)
        ->field('products', function($builder) {
            $builder->field('edges', function($edgeBuilder) {
                $edgeBuilder->field('node', [
                    'id',
                    'title',
                    'handle',
                    'status',
                    'vendor',
                    'productType',
                    'createdAt',
                    'updatedAt'
                ]);

                // Add variants conditionally
                $edgeBuilder->field('node', function($nodeBuilder) {
                    $nodeBuilder->field('variants @include(if: $includeVariants)', function($variantBuilder) {
                        $variantBuilder->field('edges', function($variantEdgeBuilder) {
                            $variantEdgeBuilder->field('node', [
                                'id',
                                'title',
                                'price',
                                'sku',
                                'inventoryQuantity',
                                'availableForSale'
                            ]);
                        });
                    });
                });

                // Add metafields
                $edgeBuilder->field('node', function($nodeBuilder) {
                    $nodeBuilder->field('metafields', function($metafieldBuilder) {
                        $metafieldBuilder->field('edges', function($metafieldEdgeBuilder) {
                            $metafieldEdgeBuilder->field('node', [
                                'id',
                                'namespace',
                                'key',
                                'value',
                                'type'
                            ]);
                        });
                    });
                });
            });

            $builder->field('pageInfo', [
                'hasNextPage',
                'hasPreviousPage',
                'startCursor',
                'endCursor'
            ]);
        });

    $response = $client->query($complexQuery->build(), [
        'first' => 5,
        'includeVariants' => true
    ]);

    if ($response->isSuccessful()) {
        $products = $response->get('products.edges', []);
        foreach ($products as $productEdge) {
            $product = $productEdge['node'];
            echo "Product: {$product['title']}\n";

            $variants = $product['variants']['edges'] ?? [];
            if (!empty($variants)) {
                echo "  Variants:\n";
                foreach ($variants as $variantEdge) {
                    $variant = $variantEdge['node'];
                    echo "    - {$variant['title']}: {$variant['price']}\n";
                }
            }

            $metafields = $product['metafields']['edges'] ?? [];
            if (!empty($metafields)) {
                echo "  Metafields:\n";
                foreach ($metafields as $metafieldEdge) {
                    $metafield = $metafieldEdge['node'];
                    echo "    - {$metafield['namespace']}.{$metafield['key']}: {$metafield['value']}\n";
                }
            }
            echo "\n";
        }
    }

    // Advanced Example 2: Bulk operation for creating multiple products
    echo "=== Bulk Product Creation ===\n";

    $bulkProducts = [
        [
            'title' => 'Bulk Product 1',
            'productType' => 'Electronics',
            'vendor' => 'Bulk Vendor',
            'status' => 'DRAFT'
        ],
        [
            'title' => 'Bulk Product 2',
            'productType' => 'Clothing',
            'vendor' => 'Bulk Vendor',
            'status' => 'DRAFT'
        ]
    ];

    foreach ($bulkProducts as $index => $productData) {
        $mutation = QueryBuilder::mutation("CreateProduct{$index}")
            ->variable('input', 'ProductInput!')
            ->field('productCreate', function($builder) {
                $builder->field('product', [
                    'id',
                    'title',
                    'handle',
                    'status'
                ]);
                $builder->field('userErrors', [
                    'field',
                    'message'
                ]);
            });

        $response = $client->mutate($mutation->build(), ['input' => $productData]);

        if ($response->isSuccessful()) {
            $product = $response->get('productCreate.product');
            echo "Created: {$product['title']} (ID: {$product['id']})\n";
        } else {
            $errors = $response->get('productCreate.userErrors', []);
            echo "Failed to create {$productData['title']}:\n";
            foreach ($errors as $error) {
                echo "  - {$error['message']}\n";
            }
        }

        // Add delay to respect rate limits
        usleep(100000); // 100ms delay
    }

    // Advanced Example 3: Using fragments for reusable field sets
    echo "\n=== Using Fragments ===\n";

    $fragmentQuery = QueryBuilder::query('GetProductsWithFragment')
        ->fragment('ProductInfo', 'Product', [
            'id',
            'title',
            'handle',
            'status',
            'vendor',
            'createdAt'
        ])
        ->variable('first', 'Int!', 3)
        ->field('products', function($builder) {
            $builder->field('edges', function($edgeBuilder) {
                $edgeBuilder->field('node')
                          ->useFragment('ProductInfo');
            });
        });

    $response = $client->query($fragmentQuery->build(), ['first' => 3]);

    if ($response->isSuccessful()) {
        $products = $response->get('products.edges', []);
        echo "Products using fragment:\n";
        foreach ($products as $edge) {
            $product = $edge['node'];
            echo "- {$product['title']} by {$product['vendor']}\n";
        }
    }

    // Advanced Example 4: Rate limit monitoring
    echo "\n=== Rate Limit Information ===\n";
    $rateLimitInfo = $response->getRateLimitInfo();
    if (!empty($rateLimitInfo)) {
        echo "Current rate limit status:\n";
        foreach ($rateLimitInfo as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'hasGraphqlErrors') && $e->hasGraphqlErrors()) {
        echo "GraphQL Errors:\n";
        foreach ($e->getGraphqlErrors() as $error) {
            echo "  - " . ($error['message'] ?? 'Unknown error') . "\n";
        }
    }
}
