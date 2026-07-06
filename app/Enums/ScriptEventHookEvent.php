<?php

namespace App\Enums;

use App\Contracts\VitoEnum;

enum ScriptEventHookEvent: string implements VitoEnum
{
    case SITE_CREATED = 'site_created';
    case SITE_DELETED = 'site_deleted';
    case SERVER_INSTALLED = 'server_installed';
    case SERVER_DELETED = 'server_deleted';
    case SERVICE_INSTALLED = 'service_installed';
    case SERVICE_UNINSTALLED = 'service_uninstalled';

    public function getColor(): string
    {
        return 'gray';
    }

    public function getText(): string
    {
        return match ($this) {
            self::SITE_CREATED => 'Site Created',
            self::SITE_DELETED => 'Site Deleted',
            self::SERVER_INSTALLED => 'Server Installed',
            self::SERVER_DELETED => 'Server Deleted',
            self::SERVICE_INSTALLED => 'Service Installed',
            self::SERVICE_UNINSTALLED => 'Service Uninstalled',
        };
    }

    /**
     * @return array<string, string>
     */
    public function variables(): array
    {
        return match ($this) {
            self::SITE_CREATED => [
                'site_domain' => 'The domain of the created site',
                'site_path' => 'The path of the created site',
                'site_type' => 'The type of the created site',
                'server_name' => 'The name of the server',
                'server_ip' => 'The IP address of the server',
            ],
            self::SITE_DELETED => [
                'site_domain' => 'The domain of the deleted site',
                'server_name' => 'The name of the server',
                'server_ip' => 'The IP address of the server',
            ],
            self::SERVER_INSTALLED => [
                'server_name' => 'The name of the installed server',
                'server_ip' => 'The IP address of the installed server',
            ],
            self::SERVER_DELETED => [
                'server_name' => 'The name of the deleted server',
                'server_ip' => 'The IP address of the deleted server',
            ],
            self::SERVICE_INSTALLED => [
                'service_name' => 'The name of the installed service',
                'service_type' => 'The type of the installed service',
                'service_version' => 'The version of the installed service',
                'server_name' => 'The name of the server',
                'server_ip' => 'The IP address of the server',
            ],
            self::SERVICE_UNINSTALLED => [
                'service_name' => 'The name of the uninstalled service',
                'service_type' => 'The type of the uninstalled service',
                'server_name' => 'The name of the server',
                'server_ip' => 'The IP address of the server',
            ],
        };
    }
}
