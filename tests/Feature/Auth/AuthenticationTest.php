<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Ssr\Gateway;
use Inertia\Testing\AssertableInertia;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('login screen can be rendered', function (bool $ssrEnabled) {
    config()->set('inertia.ssr.enabled', $ssrEnabled);
    $this->mock(Gateway::class, function (MockInterface $mock): void {
        $mock->shouldReceive('dispatch')->andReturnNull();
    });

    $this->get('/login')
        ->assertSuccessful()
        ->assertDontSee('/ziggy/', false)
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/login')->missing('ziggy'));
})->with([false, true]);

test('client route catalogue is not served', function () {
    $this->get('/ziggy/deadbeef.js')->assertNotFound();
});

test('users can authenticate using the login screen', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('servers', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $user->ensureHasDefaultProject();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('rotating x-forwarded-for from an untrusted client does not reset the login throttle', function () {
    /** @var User $user */
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('X-Forwarded-For', '198.51.100.'.$attempt)
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(302);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeader('X-Forwarded-For', '198.51.100.99')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertTooManyRequests();
});

test('forwarded client ip is honored from a trusted private proxy', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeader('X-Forwarded-For', '198.51.100.7')
        ->get('/login');

    expect(request()->ip())->toBe('198.51.100.7');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeader('X-Forwarded-For', '198.51.100.7')
        ->get('/login');

    expect(request()->ip())->toBe('203.0.113.10');
});

test('login attempts for one email are limited across client addresses', function () {
    /** @var User $user */
    $user = User::factory()->create();

    foreach (range(1, 10) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.'.$attempt])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertStatus(302);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertTooManyRequests();
});
