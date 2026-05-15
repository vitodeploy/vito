<?php

namespace Tests\Feature\API;

use App\Enums\FirewallRuleStatus;
use App\Facades\SSH;
use App\Models\FirewallRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FirewallTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_firewall_rule(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'Test',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '1234',
            'source' => '0.0.0.0',
            'mask' => '1',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'port' => '1234',
                'status' => FirewallRuleStatus::CREATING,
            ]);
    }

    public function test_create_firewall_rule_with_integer_port_input(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'IntPort',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => 1234,
            'source' => null,
            'mask' => null,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'port' => '1234',
            ]);
    }

    public function test_create_firewall_rule_with_port_range(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'RangeAPI',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '3000:3010',
            'source' => null,
            'mask' => null,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'port' => '3000:3010',
            ]);
    }

    public function test_edit_firewall_rule(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
            'port' => '1234',
        ]);

        $this->json('PUT', route('api.projects.servers.firewall-rules.edit', [
            'project' => $this->server->project,
            'server' => $this->server,
            'firewallRule' => $rule,
        ]), [
            'name' => 'Test',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '55',
            'source' => null,
            'mask' => null,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'port' => '55',
                'status' => FirewallRuleStatus::UPDATING,
            ]);
    }

    public function test_see_firewall_rules(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var FirewallRule $rule */
        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.firewall-rules', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'source' => $rule->source,
                'port' => $rule->port,
            ]);
    }

    public function test_delete_firewall_rule(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var FirewallRule $rule */
        $rule = FirewallRule::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.firewall-rules.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'firewallRule' => $rule,
        ]))
            ->assertNoContent();
    }

    #[DataProvider('nonScalarPortProvider')]
    public function test_rejects_non_string_or_int_port_input(mixed $port): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'BadType',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => $port,
            'source' => null,
            'mask' => null,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['port']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function nonScalarPortProvider(): array
    {
        return [
            'true boolean' => [true],
            'false boolean' => [false],
            'float' => [22.5],
            'array' => [[22]],
            'null' => [null],
        ];
    }

    #[DataProvider('invalidPortProvider')]
    public function test_rejects_invalid_port_input(string $port): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.firewall-rules.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'BadPort',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => $port,
            'source' => null,
            'mask' => null,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['port']);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function invalidPortProvider(): array
    {
        return [
            'zero' => ['0'],
            'above max' => ['65536'],
            'far above max' => ['99999'],
            'both above max' => ['100000:200000'],
            'second side above max' => ['1000:65536'],
            'reversed range' => ['5000:4000'],
            'equal range' => ['3000:3000'],
            'non-numeric' => ['abc'],
            'trailing colon' => ['3000:'],
            'leading colon' => [':3010'],
            'leading zero' => ['01234'],
            'negative' => ['-22'],
            'decimal' => ['22.5'],
            'whitespace inside' => ['3000 : 3010'],
            'three parts' => ['1000:2000:3000'],
            'empty' => [''],
        ];
    }
}
