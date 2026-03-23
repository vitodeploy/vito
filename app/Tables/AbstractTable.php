<?php

namespace App\Tables;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class AbstractTable
{
    protected Builder|Relation $query;

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDir = 'desc';

    protected int $perPage;

    protected string $pageName = 'page';

    protected ?string $realtimeEvent = null;

    public function __construct(Builder|Relation $query)
    {
        $this->query = $query;
        $this->perPage ??= config('web.pagination_size', 10);
    }

    public static function make(Builder|Relation $query): static
    {
        /** @phpstan-ignore new.static */
        return new static($query);
    }

    /**
     * @return array<int, Column>
     */
    abstract protected function columns(): array;

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $columns = $this->columns();

        $this->applySearch();
        $this->applySorting($columns);

        $paginated = $this->query->simplePaginate($this->perPage, pageName: $this->pageName);

        $paginated = $paginated->through(fn ($model) => $this->rowFromModel($model, $columns));

        $arr = $paginated->toArray();

        return [
            'columns' => array_map(fn (Column $col) => $col->toArray(), $columns),
            'data' => $arr['data'],
            'links' => [
                'first' => $arr['first_page_url'] ?? null,
                'last' => $arr['last_page_url'] ?? null,
                'prev' => $arr['prev_page_url'] ?? null,
                'next' => $arr['next_page_url'] ?? null,
            ],
            'meta' => [
                'current_page' => $arr['current_page'],
                'from' => $arr['from'],
                'path' => $arr['path'],
                'per_page' => $arr['per_page'],
                'to' => $arr['to'],
                'current_page_url' => $arr['path'].'?'.$this->pageName.'='.$arr['current_page'],
            ],
            'searchable' => count($this->searchable()) > 0,
            'realtimeEvent' => $this->realtimeEvent,
        ];
    }

    public function perPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function pageName(string $pageName): static
    {
        $this->pageName = $pageName;

        return $this;
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<string, mixed>
     */
    protected function rowFromModel(mixed $model, array $columns): array
    {
        $row = [];

        foreach ($columns as $column) {
            $row[$column->getName()] = $column->getValue($model);

            foreach ($column->resolveDisplayValues($model) as $key => $value) {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    protected function applySearch(): void
    {
        $search = request()->input('search');
        $searchable = $this->searchable();

        if (empty($search) || empty($searchable)) {
            return;
        }

        $this->query->where(function ($q) use ($search, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'like', '%'.$search.'%');
            }
        });
    }

    /**
     * @param  array<int, Column>  $columns
     */
    protected function applySorting(array $columns): void
    {
        $userSortBy = request()->input('sort_by');
        $userSortDir = request()->input('sort_dir');

        $sortableMap = [];
        foreach ($columns as $col) {
            if ($col->isSortable()) {
                $sortableMap[$col->getSortKey()] = $col->getAccessor();
            }
        }

        if ($userSortBy && isset($sortableMap[$userSortBy])) {
            $dir = strtolower($userSortDir ?? 'desc') === 'asc' ? 'asc' : 'desc';
            $this->query->reorder()->orderBy($sortableMap[$userSortBy], $dir);
        } elseif (empty($this->query->getQuery()->orders)) {
            $this->query->orderBy($this->defaultSort, $this->defaultSortDir);
        }
    }
}
