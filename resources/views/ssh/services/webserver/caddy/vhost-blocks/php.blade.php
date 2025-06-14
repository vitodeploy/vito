#[php]
root * {{ $site->getWebDirectoryPath() }}
@php
    $phpSocket = "unix//var/run/php/php{$site->php_version}-fpm.sock";
    if ($site->isIsolated()) {
        $phpSocket = "unix//run/php/php{$site->php_version}-fpm-{$site->user}.sock";
    }
@endphp
try_files {path} {path}/ /index.php?{query}
php_fastcgi {{ $phpSocket }}
file_server
#[/php]
