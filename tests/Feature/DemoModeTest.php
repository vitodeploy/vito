<?php

use App\Providers\DemoServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo' => true]);

    $runningInConsole = new ReflectionProperty(app(), 'isRunningInConsole');
    $runningInConsole->setValue(app(), false);
    app()->register(DemoServiceProvider::class, true);
    $runningInConsole->setValue(app(), true);
});

test('demo allows user preference fields to change', function () {
    $this->user->update(['timezone' => 'Europe/Berlin']);

    expect($this->user->refresh()->timezone)->toBe('Europe/Berlin');
});

test('demo rejects changes to user authority and identity fields', function (array $attributes) {
    expect(fn () => $this->user->update($attributes))->toThrow(HttpException::class);
})->with([
    'is_admin' => [['is_admin' => false]],
    'email' => [['email' => 'demo@example.com']],
    'mixed with allowed field' => [['timezone' => 'Europe/Berlin', 'is_admin' => false]],
]);
