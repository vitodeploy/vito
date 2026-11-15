<?php

namespace App\WebSocket;

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

class EventsHandler implements WebSocketHandler
{
    use RevokesConnections;

    protected const MAX_CONNECTIONS_PER_USER = 10;

    protected const SUBSCRIBE_RATE_LIMIT = 20;

    protected const SUBSCRIBE_RATE_WINDOW = 60;

    /**
     * @var array<string, array{
     *     connection: WebSocketConnection,
     *     user_id: int,
     *     project_id: int,
     * }>
     */
    protected array $connections = [];

    /**
     * @var array<int, array<int, float>>
     */
    protected array $userSubscribeTimestamps = [];

    /**
     * @var array<string, array{user_id: int, project_id: int}>
     */
    protected array $pendingAuth = [];

    /**
     * Map of project IDs to connection IDs subscribed to that project.
     *
     * @var array<int, array<string, true>>
     */
    protected array $projectSubscriptions = [];

    public function authenticate(RequestInterface $psrRequest): ?string
    {
        $queryString = $psrRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);
        $token = $queryParams['token'] ?? '';

        if (empty($token)) {
            return 'Missing authentication token';
        }

        $tokenData = (new GenerateWebSocketToken)->validate('events_token', $token);
        if ($tokenData === null) {
            return 'Invalid or expired token';
        }

        $user = User::find($tokenData['user_id']);
        if (! $user) {
            return 'User not found';
        }

        if ($this->userConnectionCount($tokenData['user_id']) >= self::MAX_CONNECTIONS_PER_USER) {
            return 'Too many connections';
        }

        $projectId = $tokenData['project_id'];
        if (! $projectId) {
            return 'No active project';
        }

        if ($this->authorizedUserIds([$user->id], $projectId) === []) {
            return 'Unauthorized for project';
        }

        // Store validated data for retrieval in onOpen()
        $this->pendingAuth[$token] = [
            'user_id' => $tokenData['user_id'],
            'project_id' => $projectId,
        ];

        return null;
    }

    public function onOpen(string $connId, WebSocketConnection $connection, RequestInterface $psrRequest): void
    {
        $queryString = $psrRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);
        $token = $queryParams['token'] ?? '';

        $authData = $this->pendingAuth[$token] ?? null;
        unset($this->pendingAuth[$token]);

        if (! $authData) {
            $connection->send(['type' => 'error', 'message' => 'Authentication failed']);
            $connection->close();

            return;
        }

        $this->connections[$connId] = [
            'connection' => $connection,
            'user_id' => $authData['user_id'],
            'project_id' => $authData['project_id'],
        ];

        $projectId = $authData['project_id'];
        $this->projectSubscriptions[$projectId][$connId] = true;

        $connection->send([
            'type' => 'connected',
            'project_id' => $projectId,
        ]);
    }

    public function onMessage(string $connId, string $payload): void
    {
        $entry = $this->connections[$connId] ?? null;
        if (! $entry) {
            return;
        }

        try {
            $data = json_decode($payload, true);
            if (! is_array($data) || ! isset($data['type'])) {
                return;
            }

            match ($data['type']) {
                'subscribe' => $this->handleSubscribe($connId, $data),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Events handler message error', ['error' => $e->getMessage()]);
        }
    }

    public function onClose(string $connId): void
    {
        $entry = $this->connections[$connId] ?? null;
        if ($entry) {
            $projectId = $entry['project_id'];
            $userId = $entry['user_id'];
            unset($this->projectSubscriptions[$projectId][$connId]);
            if (empty($this->projectSubscriptions[$projectId])) {
                unset($this->projectSubscriptions[$projectId]);
            }
            unset($this->connections[$connId]);

            if ($this->userConnectionCount($userId) === 0) {
                unset($this->userSubscribeTimestamps[$userId]);
            }
        }
    }

    public function ping(string $connId): void
    {
        $entry = $this->connections[$connId] ?? null;
        if (! $entry) {
            return;
        }

        if ($this->authorizedUserIds([$entry['user_id']], $entry['project_id']) === []) {
            $this->revoke($connId);

            return;
        }

        $entry['connection']->ping();
    }

    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * Broadcast an event to the connections subscribed to a project, or only
     * to one user's connections when a user id is given.
     *
     * @param  array<string, mixed>  $eventData
     */
    public function broadcastToProject(int $projectId, array $eventData, ?int $userId = null): void
    {
        $this->deliver(array_intersect_key($this->connections, $this->projectSubscriptions[$projectId] ?? []), $eventData, $userId, $projectId);
    }

    /**
     * Broadcast an event to every connected client regardless of project, or
     * only to one user's connections when a user id is given.
     *
     * @param  array<string, mixed>  $eventData
     */
    public function broadcastToAll(array $eventData, ?int $userId = null): void
    {
        $this->deliver($this->connections, $eventData, $userId);
    }

    /**
     * Get all project IDs that have active subscriptions.
     *
     * @return array<int>
     */
    public function getSubscribedProjectIds(): array
    {
        return array_keys($this->projectSubscriptions);
    }

    protected function isSubscribeRateLimited(int $userId): bool
    {
        $now = microtime(true);
        $timestamps = $this->userSubscribeTimestamps[$userId] ?? [];

        $timestamps = array_filter($timestamps, fn (float $ts) => ($now - $ts) < self::SUBSCRIBE_RATE_WINDOW);
        $this->userSubscribeTimestamps[$userId] = array_values($timestamps);

        if (count($timestamps) >= self::SUBSCRIBE_RATE_LIMIT) {
            return true;
        }

        $this->userSubscribeTimestamps[$userId][] = $now;

        return false;
    }

    protected function handleSubscribe(string $connId, array $data): void
    {
        $entry = $this->connections[$connId] ?? null;
        if (! $entry) {
            return;
        }

        if ($this->isSubscribeRateLimited($entry['user_id'])) {
            $entry['connection']->send([
                'type' => 'error',
                'message' => 'Subscribe rate limit exceeded',
            ]);

            return;
        }

        $newProjectId = (int) ($data['project_id'] ?? 0);
        if ($newProjectId <= 0) {
            return;
        }

        if ($this->authorizedUserIds([$entry['user_id']], $newProjectId) === []) {
            $entry['connection']->send([
                'type' => 'error',
                'message' => 'Unauthorized for project',
            ]);

            return;
        }

        // Remove from old project subscription
        $oldProjectId = $entry['project_id'];
        unset($this->projectSubscriptions[$oldProjectId][$connId]);
        if (empty($this->projectSubscriptions[$oldProjectId])) {
            unset($this->projectSubscriptions[$oldProjectId]);
        }

        // Add to new project subscription
        $this->connections[$connId]['project_id'] = $newProjectId;
        $this->projectSubscriptions[$newProjectId][$connId] = true;

        $entry['connection']->send([
            'type' => 'subscribed',
            'project_id' => $newProjectId,
        ]);
    }

    /**
     * Access is checked again on every delivery, so a removed member or a
     * deleted user stops receiving events and is disconnected.
     *
     * @param  array<string, array{connection: WebSocketConnection, user_id: int, project_id: int}>  $entries
     * @param  array<string, mixed>  $eventData
     */
    protected function deliver(array $entries, array $eventData, ?int $userId, ?int $projectId = null): void
    {
        if ($userId !== null) {
            $entries = array_filter($entries, fn (array $entry): bool => $entry['user_id'] === $userId);
        }

        if ($entries === []) {
            return;
        }

        $authorized = $this->authorizedUserIds(array_column($entries, 'user_id'), $projectId);

        foreach ($entries as $connId => $entry) {
            if (! in_array($entry['user_id'], $authorized, true)) {
                $this->revoke($connId);

                continue;
            }

            $entry['connection']->send([
                'type' => 'event',
                'data' => $eventData,
            ]);
        }
    }

    /**
     * @param  array<int>  $userIds
     * @return array<int>
     */
    protected function authorizedUserIds(array $userIds, ?int $projectId): array
    {
        return User::query()
            ->whereIn('id', array_unique($userIds))
            ->when($projectId !== null, fn (Builder $query) => $query->whereHas(
                'projects',
                fn (Builder $query) => $query->where('projects.id', $projectId),
            ))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    protected function userConnectionCount(int $userId): int
    {
        return count(array_filter(
            $this->connections,
            fn (array $entry) => $entry['user_id'] === $userId,
        ));
    }
}
