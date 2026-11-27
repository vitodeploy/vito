<?php

use App\Models\Workflow;
use App\ValidationRules\PublicUrlRule;
use App\WorkflowActions\General\HttpCall;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    $this->action = new HttpCall($this->user, Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]));
});

test('http call rejects internal destinations', function (string $url) {
    Http::fake();

    expect(fn () => $this->action->run(['url' => $url, 'method' => 'GET']))
        ->toThrow(ValidationException::class);

    Http::assertNothingSent();
})->with([
    'loopback' => 'http://127.0.0.1:9999/secret.txt',
    'other loopback' => 'http://127.0.0.2/',
    'localhost name' => 'http://localhost/',
    'cloud metadata' => 'http://169.254.169.254/latest/meta-data/',
    'private lan' => 'http://10.0.0.5/',
    'private 192' => 'http://192.168.1.1/',
    'carrier nat' => 'http://100.64.0.1/',
    'ipv6 loopback' => 'http://[::1]/',
    'ipv4 mapped ipv6' => 'http://[::ffff:127.0.0.1]/',
    'nat64 loopback' => 'http://[64:ff9b::7f00:1]/',
    '6to4 loopback' => 'http://[2002:7f00:1::]/',
    'teredo' => 'http://[2001:0:4136:e378:8000:63bf:3fff:fdd2]/',
    'ipv4 compatible loopback' => 'http://[::7f00:1]/',
    'decimal loopback' => 'http://2130706433/',
    'unresolvable host' => 'http://vito-ssrf-test.invalid/',
    'non http scheme' => 'file:///etc/passwd',
]);

test('http call reaches public destinations without following redirects', function () {
    Http::fake(['1.1.1.1/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/'])]);

    $output = $this->action->run(['url' => 'https://1.1.1.1/hook', 'method' => 'GET']);

    expect($output['status_code'])->toBe(302)
        ->and($output['success'])->toBeFalse();
    Http::assertSentCount(1);
});

test('http call allows private destinations when enabled in config', function () {
    config()->set('workflow.http_call_allow_private_networks', true);
    Http::fake(['127.0.0.1:9999/*' => Http::response('internal')]);

    $output = $this->action->run(['url' => 'http://127.0.0.1:9999/health', 'method' => 'GET']);

    expect($output['response_body_raw'])->toBe('internal');
});

test('public url rule pins resolved host names and rejects mixed answers', function () {
    $rule = new class extends PublicUrlRule
    {
        /** @var list<string> */
        public array $resolved = [];

        protected function addresses(string $host): array
        {
            return $this->resolved;
        }
    };

    $rule->resolved = ['1.1.1.1', '2606:4700::1111'];
    expect(Validator::make(['url' => 'https://hooks.example.com/x'], ['url' => [$rule]])->passes())->toBeTrue()
        ->and($rule->options())->toBe(['proxy' => ['no' => ['*']], 'curl' => [CURLOPT_RESOLVE => ['hooks.example.com:443:1.1.1.1']]]);

    $rule->resolved = ['1.1.1.1', '10.0.0.1'];
    expect(Validator::make(['url' => 'http://rebind.example.com:8080/'], ['url' => [$rule]])->passes())->toBeFalse()
        ->and($rule->options())->toBe(['proxy' => ['no' => ['*']]]);
});
