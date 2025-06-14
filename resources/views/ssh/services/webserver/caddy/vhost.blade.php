{{ $site->domain }} {{ $site->getAliasesString() }} {
#[main]
@foreach($main ?? [] as $main)
{{ $main }}
@endforeach
#[/main]
}
