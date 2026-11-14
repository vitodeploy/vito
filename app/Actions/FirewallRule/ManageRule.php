<?php

namespace App\Actions\FirewallRule;

use App\Enums\FirewallRuleStatus;
use App\Jobs\FirewallRule\ApplyRulesJob;
use App\Models\EnvironmentAttachment;
use App\Models\FirewallRule;
use App\Models\Server;
use App\Support\Cidr;
use App\ValidationRules\PortOrPortRangeRule;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageRule
{
    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function create(Server $server, array $input, bool $componentManaged = false, ?EnvironmentAttachment $attachment = null): FirewallRule
    {
        $input = $this->normalizePort($input);
        $this->validate($input);

        if ($attachment && $attachment->server_id !== $server->id) {
            throw ValidationException::withMessages(['resource' => 'The resource belongs to another server.']);
        }

        $rule = $this->persist($server, $input, $componentManaged || $attachment !== null, $attachment);

        if (! $rule->component_managed) {
            $this->queueApply($rule);
        }

        return $rule;
    }

    /**
     * Creates one rule per source in `sources` (IPs or CIDRs separated by new lines,
     * commas or spaces) and applies them in a single firewall run.
     *
     * @param  array<string, mixed>  $input
     * @return Collection<int, FirewallRule>
     */
    public function createMany(Server $server, array $input): Collection
    {
        if (($input['source_any'] ?? false) === true || ! array_key_exists('sources', $input)) {
            return collect([$this->create($server, $input)]);
        }

        $input = $this->normalizePort($input);
        $input['sources'] = is_string($input['sources'])
            ? preg_split('/[\s,]+/', $input['sources'], -1, PREG_SPLIT_NO_EMPTY) ?: []
            : [];

        Validator::make($input, [
            ...Arr::except($this->rules($input), ['source', 'mask']),
            'sources' => [
                'required',
                'array',
                'max:100',
                function (string $attribute, array $value, Closure $fail): void {
                    foreach ($value as $source) {
                        if (! Cidr::isValidAddress($source) && (! Cidr::isValid($source) || Cidr::prefix($source) < 1)) {
                            $fail("{$source} is not a valid IP address or CIDR.");
                        }
                    }
                },
            ],
        ])->validate();

        $rules = DB::transaction(fn (): Collection => collect($input['sources'])
            ->map(fn (string $source): string => Cidr::network(Cidr::address($source).'/'.Cidr::bits($source)).'/'.Cidr::prefix($source))
            ->unique()
            ->map(fn (string $source): FirewallRule => $this->persist($server, [
                ...$input,
                'source_any' => false,
                'source' => Cidr::address($source),
                'mask' => Cidr::prefix($source),
            ]))
            ->values());

        dispatch(new ApplyRulesJob($server, $rules->pluck('id')->all()))->onQueue('ssh');

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return FirewallRule $rule
     */
    public function update(FirewallRule $rule, array $input): FirewallRule
    {
        $input = $this->normalizePort($input);
        $this->validate($input);

        $rule->update($this->attributesFromInput($input, FirewallRuleStatus::UPDATING));

        if (! $rule->component_managed) {
            $this->queueApply($rule);
        }

        return $rule;
    }

    public function delete(FirewallRule $rule): void
    {
        $rule->status = FirewallRuleStatus::DELETING;
        $rule->save();

        if (! $rule->component_managed) {
            $this->queueApply($rule);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function persist(Server $server, array $input, bool $componentManaged = false, ?EnvironmentAttachment $attachment = null): FirewallRule
    {
        $rule = new FirewallRule($this->attributesFromInput($input, FirewallRuleStatus::CREATING));
        $rule->server_id = $server->id;
        $rule->component_managed = $componentManaged;
        $rule->environment_attachment_id = $attachment?->id;
        $rule->save();

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input): void
    {
        Validator::make($input, $this->rules($input))->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function rules(array $input): array
    {
        $source = $input['source'] ?? null;
        $maxMask = is_string($source) && Cidr::isValidAddress($source) ? Cidr::bits($source) : 32;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:18',
            ],
            'type' => [
                'required',
                'in:allow,deny',
            ],
            'protocol' => [
                'required',
                'in:tcp,udp',
            ],
            'port' => [
                'required',
                new PortOrPortRangeRule,
            ],
            'source' => [
                'nullable',
                'ip',
            ],
            'mask' => [
                'nullable',
                'numeric',
                'min:1',
                'max:'.$maxMask,
            ],
        ];

        if (isset($input['source_any']) && $input['source_any'] === false) {
            $rules['source'] = ['required', 'ip'];
            $rules['mask'] = ['required', 'numeric', 'min:1', 'max:'.$maxMask];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizePort(array $input): array
    {
        $port = $input['port'] ?? null;
        $input['port'] = is_string($port) || is_int($port) ? (string) $port : null;

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function attributesFromInput(array $input, FirewallRuleStatus $status): array
    {
        $sourceAny = $input['source_any'] ?? empty($input['source'] ?? null);

        return [
            'name' => $input['name'],
            'type' => $input['type'],
            'protocol' => $input['protocol'],
            'port' => $input['port'],
            'source' => $sourceAny ? null : $input['source'],
            'mask' => $sourceAny ? null : ($input['mask'] ?? null),
            'status' => $status,
        ];
    }

    private function queueApply(FirewallRule $rule): void
    {
        dispatch(new ApplyRulesJob($rule->server, [$rule->id]))->onQueue('ssh');
    }
}
