<?php

declare(strict_types=1);

namespace ShopifyGraphQL;

/**
 * GraphQL Query Builder
 * 
 * Provides a fluent interface for building GraphQL queries
 */
class QueryBuilder
{
    private string $operation = 'query';
    private string $name = '';
    private array $variables = [];
    private array $fields = [];
    private array $fragments = [];

    /**
     * Create a new query
     *
     * @param string $name Optional operation name
     * @return static
     */
    public static function query(string $name = ''): self
    {
        return (new self())->setOperation('query', $name);
    }

    /**
     * Create a new mutation
     *
     * @param string $name Optional operation name
     * @return static
     */
    public static function mutation(string $name = ''): self
    {
        return (new self())->setOperation('mutation', $name);
    }

    /**
     * Set the operation type and name
     *
     * @param string $operation
     * @param string $name
     * @return $this
     */
    private function setOperation(string $operation, string $name = ''): self
    {
        $this->operation = $operation;
        $this->name = $name;
        return $this;
    }

    /**
     * Add a variable definition
     *
     * @param string $name Variable name
     * @param string $type GraphQL type
     * @param mixed $defaultValue Optional default value
     * @return $this
     */
    public function variable(string $name, string $type, $defaultValue = null): self
    {
        $variable = "\${$name}: {$type}";
        if ($defaultValue !== null) {
            $variable .= ' = ' . $this->formatValue($defaultValue);
        }
        $this->variables[] = $variable;
        return $this;
    }

    /**
     * Add a field to the selection
     *
     * @param string $field Field name or complete field definition
     * @param array|callable|null $subfields Subfields or callback for nested fields
     * @return $this
     */
    public function field(string $field, $subfields = null): self
    {
        if ($subfields === null) {
            $this->fields[] = $field;
        } elseif (is_callable($subfields)) {
            $builder = new self();
            $subfields($builder);
            $this->fields[] = $field . ' {' . implode(' ', $builder->fields) . '}';
        } elseif (is_array($subfields)) {
            $this->fields[] = $field . ' { ' . implode(' ', $subfields) . ' }';
        }
        return $this;
    }

    /**
     * Add multiple fields at once
     *
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields): self
    {
        foreach ($fields as $field => $subfields) {
            if (is_numeric($field)) {
                $this->field($subfields);
            } else {
                $this->field($field, $subfields);
            }
        }
        return $this;
    }

    /**
     * Add a fragment definition
     *
     * @param string $name Fragment name
     * @param string $type Type condition
     * @param array $fields Fragment fields
     * @return $this
     */
    public function fragment(string $name, string $type, array $fields): self
    {
        $this->fragments[$name] = "fragment {$name} on {$type} { " . implode(' ', $fields) . ' }';
        return $this;
    }

    /**
     * Use a fragment in the current selection
     *
     * @param string $name Fragment name
     * @return $this
     */
    public function useFragment(string $name): self
    {
        $this->fields[] = "...{$name}";
        return $this;
    }

    /**
     * Build the complete GraphQL query string
     *
     * @return string
     */
    public function build(): string
    {
        $query = $this->operation;

        if (!empty($this->name)) {
            $query .= ' ' . $this->name;
        }

        if (!empty($this->variables)) {
            $query .= '(' . implode(', ', $this->variables) . ')';
        }

        $query .= ' { ' . implode(' ', $this->fields) . ' }';

        if (!empty($this->fragments)) {
            $query = implode(' ', $this->fragments) . ' ' . $query;
        }

        return $query;
    }

    /**
     * Format a value for GraphQL
     *
     * @param mixed $value
     * @return string
     */
    private function formatValue($value): string
    {
        if (is_string($value)) {
            return '"' . addslashes($value) . '"';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return '[' . implode(', ', array_map([$this, 'formatValue'], $value)) . ']';
        }

        return (string) $value;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->build();
    }
}
