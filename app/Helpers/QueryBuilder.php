<?php

namespace App\Helpers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class QueryBuilder
{
    protected array $searchableFields = [];

    protected ?string $sortBy = null;

    protected ?string $sortDir = null;

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

    public function sortable(?string $defaultSortBy, ?string $defaultSortDir): self
    {
        if (request()->has('sort_by') && request()->has('sort_dir')) {
            $sortBy = request('sort_by');
            $sortDir = request('sort_dir');

            $dir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

            $this->sortBy = $sortBy;
            $this->sortDir = $dir;
        } elseif ($defaultSortBy && $defaultSortDir) {
            $this->sortBy = $defaultSortBy;
            $this->sortDir = strtolower($defaultSortDir) === 'asc' ? 'asc' : 'desc';
        }

        return $this;
    }

    public function query(): Builder|Relation
    {
        $this->query->where(function ($query) {
            if (request()->has('search') && ! empty(request('search'))) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    foreach ($this->searchableFields as $field) {
                        $q->orWhere($field, 'like', '%'.$search.'%');
                    }
                });
            }

        });

        if ($this->sortBy && $this->sortDir) {
            $this->query->orderBy($this->sortBy, $this->sortDir);
        }

        return $this->query;
    }
}
