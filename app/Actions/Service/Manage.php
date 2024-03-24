<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;

class Manage
{
    public function start(Service $service): void
    {
        $service->status = ServiceStatus::STARTING;
        $service->save();
        dispatch(function () use ($service) {
            $status = $service->server->systemd()->start($service->unit);
            if (str($status)->contains('Active: active')) {
                $service->status = ServiceStatus::READY;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }

    public function stop(Service $service): void
    {
        $service->status = ServiceStatus::STOPPING;
        $service->save();
        dispatch(function () use ($service) {
            $status = $service->server->systemd()->stop($service->unit);
            if (str($status)->contains('Active: inactive')) {
                $service->status = ServiceStatus::STOPPED;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }

    public function restart(Service $service): void
    {
        $service->status = ServiceStatus::RESTARTING;
        $service->save();
        dispatch(function () use ($service) {
            $status = $service->server->systemd()->restart($service->unit);
            if (str($status)->contains('Active: active')) {
                $service->status = ServiceStatus::READY;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }

    public function enable(Service $service): void
    {
        $service->status = ServiceStatus::ENABLING;
        $service->save();
        dispatch(function () use ($service) {
            $status = $service->server->systemd()->enable($service->unit);
            if (str($status)->contains('Active: active')) {
                $service->status = ServiceStatus::READY;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }

    public function disable(Service $service): void
    {
        $service->status = ServiceStatus::DISABLING;
        $service->save();
        dispatch(function () use ($service) {
            $status = $service->server->systemd()->disable($service->unit);
            if (str($status)->contains('Active: inactive')) {
                $service->status = ServiceStatus::DISABLED;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }
}
