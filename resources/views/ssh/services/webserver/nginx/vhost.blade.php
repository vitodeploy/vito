#[header]
@foreach($header ?? [] as $header)
{{ $header }}
@endforeach
#[/header]

server {
    #[main]
    @foreach($main ?? [] as $main)
    {{ $main }}
    @endforeach
    #[/main]
}

#[footer]
@foreach($footer ?? [] as $footer)
{{ $footer }}
@endforeach
#[/footer]
