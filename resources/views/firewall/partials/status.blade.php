@if ($status == \App\Enums\FirewallRuleStatus::READY)
    <x-status status="success">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\FirewallRuleStatus::CREATING)
    <x-status status="warning">{{ $status }}</x-status>
@endif

@if ($status == \App\Enums\FirewallRuleStatus::DELETING)
    <x-status status="danger">{{ $status }}</x-status>
@endif
