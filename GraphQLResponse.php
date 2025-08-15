<?php

declare(strict_types=1);

namespace ShopifyGraphQL;

/**
 * GraphQL Response wrapper
 * 
 * Provides convenient access to GraphQL response data, errors, and metadata
 */
class GraphQLResponse
{
    private array $responseData;
    private array $headers;
    private array $rateLimitInfo;

    public function __construct(array $responseData, array $headers = [], array $rateLimitInfo = [])
    {
        $this->responseData = $responseData;
        $this->headers = $headers;
        $this->rateLimitInfo = $rateLimitInfo;
    }

    /**
     * Get the data portion of the response
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->responseData['data'] ?? null;
    }

    /**
     * Get errors from the response
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->responseData['errors'] ?? [];
    }

    /**
     * Check if the response has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->getErrors());
    }

    /**
     * Get extensions (metadata) from the response
     *
     * @return array
     */
    public function getExtensions(): array
    {
        return $this->responseData['extensions'] ?? [];
    }

    /**
     * Get the complete raw response data
     *
     * @return array
     */
    public function getRawData(): array
    {
        return $this->responseData;
    }

    /**
     * Get response headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get rate limit information
     *
     * @return array
     */
    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }

    /**
     * Get a specific piece of data using dot notation
     *
     * @param string $key Dot notation key (e.g., 'shop.name')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $data = $this->getData();

        if ($data === null) {
            return $default;
        }

        $keys = explode('.', $key);
        $result = $data;

        foreach ($keys as $segment) {
            if (!is_array($result) || !array_key_exists($segment, $result)) {
                return $default;
            }
            $result = $result[$segment];
        }

        return $result;
    }

    /**
     * Check if the response is successful (no errors)
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return !$this->hasErrors();
    }

    /**
     * Get the first error message if any
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        $errors = $this->getErrors();
        return !empty($errors) ? ($errors[0]['message'] ?? null) : null;
    }

    /**
     * Convert response to JSON string
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->responseData, JSON_PRETTY_PRINT);
    }

    /**
     * Magic method to convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
