#[force-ssl]
@if ($site->activeSsl && $site->force_ssl)
    redir @http https://{host}{uri} permanent
@endif
#[/force-ssl]
