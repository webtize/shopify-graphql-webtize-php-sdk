<?php

declare(strict_types=1);

namespace ShopifyGraphQL;

use Exception;

/**
 * Base exception for all Shopify GraphQL SDK errors
 */
class ShopifyGraphQLException extends Exception
{
    protected array $graphqlErrors;

    public function __construct(string $message = '', int $code = 0, Exception $previous = null, array $graphqlErrors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->graphqlErrors = $graphqlErrors;
    }

    /**
     * Get GraphQL-specific errors
     *
     * @return array
     */
    public function getGraphqlErrors(): array
    {
        return $this->graphqlErrors;
    }

    /**
     * Check if this exception contains GraphQL errors
     *
     * @return bool
     */
    public function hasGraphqlErrors(): bool
    {
        return !empty($this->graphqlErrors);
    }
}
