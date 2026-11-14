<?php

namespace App\Helpers;

use App\Actions\Network\ResolveServerAddress;
use App\Models\Component;
use App\Models\Database;
use App\Models\EnvironmentAttachment;
use App\Services\SupportsNetworking;

class AttachmentVariables
{
    public static function username(EnvironmentAttachment $attachment, Component $component): string
    {
        return ($attachment->credentials['username'] ?? '').'_'.base_convert((string) $component->id, 10, 36);
    }

    /** @return array<string, string> */
    public static function for(EnvironmentAttachment $attachment, Component $component): array
    {
        $service = $attachment->service();
        $handler = $service?->handler();
        $connection = app(ResolveServerAddress::class)->connection($component->server, $attachment->server);
        if ($attachment->isDeleting() || ! $handler instanceof SupportsNetworking || $connection === null) {
            return [];
        }
        $host = $connection['ip'];
        $urlHost = str_contains($host, ':') ? '['.$host.']' : $host;
        $port = (string) $handler->networkingPort();
        $password = $attachment->credentials['password'];
        if ($attachment->attachable instanceof Database) {
            $username = static::username($attachment, $component);
            $scheme = $service->name === 'postgresql' ? 'postgresql' : 'mysql';
            $variables = [
                'DATABASE_URL' => $scheme.'://'.rawurlencode($username).':'.rawurlencode($password).'@'.$urlHost.':'.$port.'/'.rawurlencode($attachment->attachable->name),
                'DATABASE_HOST' => $host, 'DATABASE_PORT' => $port, 'DATABASE_NAME' => $attachment->attachable->name,
                'DATABASE_USER' => $username, 'DATABASE_PASSWORD' => $password,
            ];
        } else {
            $variables = [
                'REDIS_URL' => 'redis://'.($password !== '' ? ':'.rawurlencode($password).'@' : '').$urlHost.':'.$port,
                'REDIS_HOST' => $host, 'REDIS_PORT' => $port, 'REDIS_PASSWORD' => $password,
            ];
        }
        $prefix = $attachment->alias !== null ? $attachment->alias.'_' : '';

        return array_combine(array_map(fn (string $key): string => $prefix.$key, array_keys($variables)), array_values($variables));
    }
}
