#[port]
@if (!$site->activeSsl || !$site->force_ssl)
    listen 80;
    listen [::]:80;
@endif
@if ($site->activeSsl)
    listen 443 ssl;
    listen [::]:443 ssl;
    ssl_certificate {{ $site->activeSsl->certificate_path }};
    ssl_certificate_key {{ $site->activeSsl->pk_path }};
@endif
#[/port]
