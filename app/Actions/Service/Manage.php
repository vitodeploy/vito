<?php

namespace App\Actions\Service;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

class Manage
{
    public function start(Service $service): void
    {
        $this->validate($service);
        $service->status = ServiceStatus::STARTING;
        $service->save();
        dispatch(function () use ($service): void {
            $status = $service->server->systemd()->start($service->handler()->unit());
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
        $this->validate($service);
        $service->status = ServiceStatus::STOPPING;
        $service->save();
        dispatch(function () use ($service): void {
            $status = $service->server->systemd()->stop($service->handler()->unit());
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
        $this->validate($service);
        $service->status = ServiceStatus::RESTARTING;
        $service->save();
        dispatch(function () use ($service): void {
            $status = $service->server->systemd()->restart($service->handler()->unit());
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
        $this->validate($service);
        $service->status = ServiceStatus::ENABLING;
        $service->save();
        dispatch(function () use ($service): void {
            $status = $service->server->systemd()->enable($service->handler()->unit());
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
        $this->validate($service);
        $service->status = ServiceStatus::DISABLING;
        $service->save();
        dispatch(function () use ($service): void {
            $status = $service->server->systemd()->disable($service->handler()->unit());
            if (str($status)->contains('Active: inactive')) {
                $service->status = ServiceStatus::DISABLED;
            } else {
                $service->status = ServiceStatus::FAILED;
            }
            $service->save();
        })->onConnection('ssh');
    }

    private function validate(Service $service): void
    {
        if (! $service->handler()->unit()) {
            throw ValidationException::withMessages([
                'service' => __('This service does not have a systemd unit configured.'),
            ]);
        }
    }
}
