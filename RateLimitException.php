<?php

declare(strict_types=1);

namespace ShopifyGraphQL\Exceptions;

/**
 * Exception thrown when rate limits are exceeded
 */
class RateLimitException extends ShopifyGraphQLException
{
    private int $retryAfter;

    public function __construct(string $message = '', int $retryAfter = 0, Exception $previous = null)
    {
        parent::__construct($message, 429, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Get the number of seconds to wait before retrying
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
