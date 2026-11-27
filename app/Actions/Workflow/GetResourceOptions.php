<?php

namespace App\Actions\Workflow;

use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerProvider;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetResourceOptions
{
    private const int LIMIT = 20;

    /**
     * @param  array<string, mixed>  $input
     * @return list<array{value: string, label: string, description: string|null}>
     */
    public function get(User $user, Project $project, string $resource, array $input): array
    {
        Validator::make($input, [
            'query' => ['nullable', 'string', 'max:255'],
            'parent' => ['nullable', 'integer'],
            'selected' => ['nullable', 'integer'],
        ])->validate();

        [$policy, $query, $search, $label, $description] = $this->definition($user, $project, $resource, $input['parent'] ?? null);

        Gate::forUser($user)->authorize('viewAny', $policy);

        $term = $input['query'] ?? '';
        $items = (clone $query)
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($search, $term): void {
                foreach ($search as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            }))
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        $selected = $input['selected'] ?? null;
        if ($selected && ! $items->contains('id', (int) $selected)) {
            $items = (clone $query)->whereKey($selected)->get()->concat($items);
        }

        return $items->map(fn (Model $item): array => [
            'value' => (string) $item->getKey(),
            'label' => (string) $label($item),
            'description' => $description($item),
        ])->values()->all();
    }

    /**
     * @return array{0: mixed, 1: Builder<covariant Model>, 2: list<string>, 3: Closure(mixed): string, 4: Closure(mixed): ?string}
     */
    private function definition(User $user, Project $project, string $resource, mixed $parent): array
    {
        $serverIds = $project->servers()->select('id');
        $domainIds = $project->domains()->select('id');

        return match ($resource) {
            'server' => [
                [Server::class, $project],
                $project->servers()->getQuery(),
                ['name', 'ip'],
                fn (Server $server): string => $server->name,
                fn (Server $server): string => $server->ip,
            ],
            'site' => [
                [Server::class, $project],
                Site::query()->with('server')->whereIn('server_id', $serverIds)->when($parent, fn (Builder $q) => $q->where('server_id', $parent)),
                ['domain'],
                fn (Site $site): string => $site->domain,
                fn (Site $site): string => $site->server->name,
            ],
            'database' => [
                [Server::class, $project],
                Database::query()->with('server')->whereIn('server_id', $serverIds)->when($parent, fn (Builder $q) => $q->where('server_id', $parent)),
                ['name'],
                fn (Database $database): string => $database->name,
                fn (Database $database): string => $database->server->name,
            ],
            'database_user' => [
                [Server::class, $project],
                DatabaseUser::query()->with('server')->whereIn('server_id', $serverIds)->when($parent, fn (Builder $q) => $q->where('server_id', $parent)),
                ['username'],
                fn (DatabaseUser $databaseUser): string => $databaseUser->username,
                fn (DatabaseUser $databaseUser): string => $databaseUser->server->name,
            ],
            'domain' => [
                [Domain::class, $project],
                $project->domains()->getQuery(),
                ['domain'],
                fn (Domain $domain): string => $domain->domain,
                fn (Domain $domain): ?string => null,
            ],
            'dns_record' => [
                [Domain::class, $project],
                DNSRecord::query()->with('domain')->whereIn('domain_id', $domainIds)->when($parent, fn (Builder $q) => $q->where('domain_id', $parent)),
                ['name', 'content'],
                fn (DNSRecord $record): string => "{$record->type} {$record->name}",
                fn (DNSRecord $record): string => "{$record->content} · {$record->domain->domain}",
            ],
            'notification_channel' => [
                NotificationChannel::class,
                NotificationChannel::getByProjectId($project->id, $user),
                ['label'],
                fn (NotificationChannel $channel): string => $channel->label,
                fn (NotificationChannel $channel): string => $channel->provider,
            ],
            'source_control' => [
                SourceControl::class,
                SourceControl::getByProjectId($project->id, $user),
                ['profile'],
                fn (SourceControl $sourceControl): string => $sourceControl->profile,
                fn (SourceControl $sourceControl): string => $sourceControl->provider,
            ],
            'server_provider' => [
                ServerProvider::class,
                ServerProvider::getByProjectId($project->id, $user),
                ['profile'],
                fn (ServerProvider $serverProvider): string => $serverProvider->profile,
                fn (ServerProvider $serverProvider): string => $serverProvider->provider,
            ],
            default => throw new NotFoundHttpException('Unknown resource.'),
        };
    }
}
