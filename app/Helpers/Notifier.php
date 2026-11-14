<?php

namespace App\Helpers;

use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use App\Notifications\NotificationInterface;

class Notifier
{
    /**
     * Sends the notification only to channels whose owners are members of the notifiable's project.
     */
    public function send(object $notifiable, NotificationInterface $notification): void
    {
        $project = match (true) {
            $notifiable instanceof Project => $notifiable,
            $notifiable instanceof Server => $notifiable->project,
            $notifiable instanceof Site => $notifiable->server->project,
            default => null,
        };

        if (! $project) {
            return;
        }

        NotificationChannel::query()
            ->whereIn('user_id', $project->users()->whereNotNull('user_id')->select('user_id'))
            ->where(fn ($query) => $query->where('project_id', $project->id)->orWhereNull('project_id'))
            ->get()
            ->each(fn (NotificationChannel $channel) => $channel->notify($notification));
    }
}
