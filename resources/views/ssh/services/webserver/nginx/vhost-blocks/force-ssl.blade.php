#[force-ssl]
@if ($site->activeSsl && $site->force_ssl)
    server {
        listen 80;
        server_name {{ $site->domain }} {{ $site->getAliasesString() }};
        return 301 https://$host$request_uri;
    }
@endif
#[/force-ssl]
