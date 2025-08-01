<?php

namespace App\Helpers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class QueryBuilder
{
    protected array $searchableFields = [];

    public function __construct(public Builder|Relation $query) {}

    public static function for(Builder|Relation $query): self
    {
        return new self($query);
    }

    public function searchableFields(array $fields): self
    {
        $this->searchableFields = $fields;

        return $this;
    }

    public function query(): Builder|Relation
    {
        return $this->query->where(function ($query) {
            if (request()->has('search') && ! empty(request('search'))) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    foreach ($this->searchableFields as $field) {
                        $q->orWhere($field, 'like', '%'.$search.'%');
                    }
                });
            }
        });
    }
}
