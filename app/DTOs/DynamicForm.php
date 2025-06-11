<?php

namespace App\DTOs;

readonly class DynamicForm
{
    /**
     * @param  array<int, DynamicField>  $fields
     */
    public function __construct(
        private array $fields = [],
    ) {}

    /**
     * @param  array<int, DynamicField>  $fields
     */
    public static function make(array $fields): self
    {
        return new self($fields);
    }

    /**
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = $field->toArray();
        }

        return $fields;
    }
}
