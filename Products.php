<?php

declare(strict_types=1);

namespace ShopifyGraphQL\Resources;

use ShopifyGraphQL\ShopifyGraphQLClient;
use ShopifyGraphQL\GraphQLResponse;
use ShopifyGraphQL\QueryBuilder;

/**
 * Product resource helper
 * 
 * Provides convenient methods for product-related GraphQL operations
 */
class Products
{
    private ShopifyGraphQLClient $client;

    public function __construct(ShopifyGraphQLClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get a list of products
     *
     * @param int $first Number of products to fetch
     * @param string|null $after Cursor for pagination
     * @param array $fields Additional fields to fetch
     * @return GraphQLResponse
     */
    public function list(int $first = 10, ?string $after = null, array $fields = []): GraphQLResponse
    {
        $defaultFields = [
            'id',
            'title',
            'handle',
            'status',
            'createdAt',
            'updatedAt'
        ];

        $fields = array_merge($defaultFields, $fields);

        $query = QueryBuilder::query('GetProducts')
            ->variable('first', 'Int!')
            ->variable('after', 'String')
            ->field('products', function($builder) use ($fields) {
                $builder->field('edges', function($edgeBuilder) use ($fields) {
                    $edgeBuilder->field('node', $fields)
                            ->field('cursor');
                });
                $builder->field('pageInfo', [
                    'hasNextPage',
                    'hasPreviousPage',
                    'startCursor',
                    'endCursor'
                ]);
            });

        $variables = ['first' => $first];
        if ($after) {
            $variables['after'] = $after;
        }

        return $this->client->query($query->build(), $variables);
    }

    /**
     * Get a single product by ID
     *
     * @param string $id Product ID
     * @param array $fields Fields to fetch
     * @return GraphQLResponse
     */
    public function get(string $id, array $fields = []): GraphQLResponse
    {
        $defaultFields = [
            'id',
            'title',
            'handle',
            'description',
            'status',
            'vendor',
            'productType',
            'tags',
            'createdAt',
            'updatedAt'
        ];

        $fields = array_merge($defaultFields, $fields);

        $query = QueryBuilder::query('GetProduct')
            ->variable('id', 'ID!')
            ->field('product', function($builder) use ($fields) {
                $builder->fields($fields);
            });

        return $this->client->query($query->build(), ['id' => $id]);
    }

    /**
     * Create a new product
     *
     * @param array $input Product input data
     * @return GraphQLResponse
     */
    public function create(array $input): GraphQLResponse
    {
        $mutation = QueryBuilder::mutation('ProductCreate')
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

        return $this->client->mutate($mutation->build(), ['input' => $input]);
    }

    /**
     * Update an existing product
     *
     * @param string $id Product ID
     * @param array $input Product update data
     * @return GraphQLResponse
     */
    public function update(string $id, array $input): GraphQLResponse
    {
        $input['id'] = $id;

        $mutation = QueryBuilder::mutation('ProductUpdate')
            ->variable('input', 'ProductInput!')
            ->field('productUpdate', function($builder) {
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

        return $this->client->mutate($mutation->build(), ['input' => $input]);
    }

    /**
     * Delete a product
     *
     * @param string $id Product ID
     * @return GraphQLResponse
     */
    public function delete(string $id): GraphQLResponse
    {
        $mutation = QueryBuilder::mutation('ProductDelete')
            ->variable('input', 'ProductDeleteInput!')
            ->field('productDelete', function($builder) {
                $builder->field('deletedProductId')
                        ->field('userErrors', [
                            'field',
                            'message'
                        ]);
            });

        return $this->client->mutate($mutation->build(), ['input' => ['id' => $id]]);
    }

    /**
     * Search products by query
     *
     * @param string $query Search query
     * @param int $first Number of products to fetch
     * @param array $fields Additional fields to fetch
     * @return GraphQLResponse
     */
    public function search(string $query, int $first = 10, array $fields = []): GraphQLResponse
    {
        $defaultFields = [
            'id',
            'title',
            'handle',
            'status',
            'vendor'
        ];

        $fields = array_merge($defaultFields, $fields);

        $graphqlQuery = QueryBuilder::query('SearchProducts')
            ->variable('query', 'String!')
            ->variable('first', 'Int!')
            ->field('products', function($builder) use ($fields) {
                $builder->field('edges', function($edgeBuilder) use ($fields) {
                    $edgeBuilder->field('node', $fields);
                });
            });

        return $this->client->query($graphqlQuery->build(), [
            'query' => $query,
            'first' => $first
        ]);
    }
}
