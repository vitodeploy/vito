<?php

use App\Helpers\HtmxResponse;

function generate_public_key($privateKeyPath, $publicKeyPath): void
{
    chmod($privateKeyPath, 0400);
    exec("ssh-keygen -y -f {$privateKeyPath} > {$publicKeyPath}");
}

function generate_key_pair($path): void
{
    exec("ssh-keygen -t ed25519 -m PEM -N '' -f {$path}");
    chmod($path, 0400);
}

/**
 * @throws Exception
 */
function date_with_timezone($date, $timezone): string
{
    $dt = new DateTime('now', new DateTimeZone($timezone));
    $dt->setTimestamp(strtotime($date));

    return $dt->format('Y-m-d H:i:s');
}

function htmx(): HtmxResponse
{
    return new HtmxResponse();
}

function vito_version(): string
{
    $version = exec('git describe --tags');
    if (str($version)->contains('-')) {
        return str($version)->before('-').' (dev)';
    }

    return $version;
}

function convert_time_format($string): string
{
    $string = preg_replace('/(\d+)m/', '$1 minutes', $string);
    $string = preg_replace('/(\d+)s/', '$1 seconds', $string);
    $string = preg_replace('/(\d+)d/', '$1 days', $string);

    return preg_replace('/(\d+)h/', '$1 hours', $string);
}
