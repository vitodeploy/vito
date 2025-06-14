#[port]
@if ($site->activeSsl)
    tls {{ $site->activeSsl->certificate_path }} {{ $site->activeSsl->pk_path }}
@endif
#[/port]
