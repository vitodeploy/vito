<?php

use Illuminate\Support\Str;

function random_color(): string
{
    return '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function get_hostname_from_repo($repo): string
{
    $repo = Str::after($repo, '@');

    return Str::before($repo, ':');
}

function generate_uid(): string
{
    return hash('sha256', uniqid(rand(1111111111, 9999999999), true).Str::random(64).strtotime('now'));
}

// /**
//  * @param $privateKeyPath
//  * @return array|string|string[]
//  */
// function generate_public_key($privateKeyPath)
// {
//     $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
//     $publicKey = openssl_pkey_get_details($privateKey);
//
//     return $publicKey['key'];
// }

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

function make_bash_script($commands): string
{
    $script = '';
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $commands) as $line) {
        $script .= 'if ! '.$line."; then\n echo 'VITO_SSH_ERROR' && exit 1 \nfi"."\n";
    }

    return $script;
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
