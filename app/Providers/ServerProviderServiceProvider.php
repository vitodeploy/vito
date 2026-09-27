<?php

namespace App\Providers;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Enums\OperatingSystem;
use App\Plugins\RegisterServerProvider;
use App\ServerProviders\AWS;
use App\ServerProviders\Custom;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Proxmox;
use App\ServerProviders\Vultr;
use Illuminate\Support\ServiceProvider;

class ServerProviderServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->custom();
        $this->aws();
        $this->hetzner();
        $this->digitalOcean();
        $this->linode();
        $this->vultr();
        $this->proxmox();
    }

    private function custom(): void
    {
        RegisterServerProvider::make(Custom::id())
            ->label('Custom')
            ->handler(Custom::class)
            ->defaultUser('root')
            ->register();
    }

    private function aws(): void
    {
        RegisterServerProvider::make(AWS::id())
            ->label('AWS')
            ->handler(AWS::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('key')
                        ->text()
                        ->label('Access Key'),
                    DynamicField::make('secret')
                        ->text()
                        ->label('Secret'),
                ])
            )
            ->defaultUser('ubuntu')
            ->register();
    }

    private function hetzner(): void
    {
        RegisterServerProvider::make(Hetzner::id())
            ->label('Hetzner')
            ->handler(Hetzner::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function digitalOcean(): void
    {
        RegisterServerProvider::make(DigitalOcean::id())
            ->label('DigitalOcean')
            ->handler(DigitalOcean::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function linode(): void
    {
        RegisterServerProvider::make(Linode::id())
            ->label('Linode')
            ->handler(Linode::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function vultr(): void
    {
        RegisterServerProvider::make(Vultr::id())
            ->label('Vultr')
            ->handler(Vultr::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('token')
                        ->text()
                        ->label('Token'),
                ])
            )
            ->defaultUser('root')
            ->register();
    }

    private function proxmox(): void
    {
        RegisterServerProvider::make(Proxmox::id())
            ->label('Proxmox VE')
            ->handler(Proxmox::class)
            ->form(
                DynamicForm::make([
                    DynamicField::make('proxmox_guide')
                        ->guide($this->proxmoxGuide())
                        ->label('Proxmox setup guide')
                        ->description('Requires Proxmox VE 8.1+, an API token and a cloud-init template for each Ubuntu version.'),
                    DynamicField::make('api_url')
                        ->text()
                        ->half()
                        ->label('API URL')
                        ->placeholder('https://proxmox.example.com:8006'),
                    DynamicField::make('storage')
                        ->text()
                        ->half()
                        ->label('Disk Storage')
                        ->placeholder('Same as template'),
                    DynamicField::make('token_id')
                        ->text()
                        ->half()
                        ->label('API Token ID')
                        ->placeholder('vito@pve!vito'),
                    DynamicField::make('token_secret')
                        ->password()
                        ->half()
                        ->label('API Token Secret'),
                    ...array_map(
                        fn (string $os): DynamicField => DynamicField::make(Proxmox::templateField($os))
                            ->text()
                            ->half()
                            ->label('Ubuntu '.OperatingSystem::from($os)->getVersion().' Template')
                            ->placeholder('VMID'),
                        config('core.operating_systems'),
                    ),
                    DynamicField::make('verify_ssl')
                        ->checkbox()
                        ->label('Verify SSL certificate')
                        ->default(true),
                ])
            )
            ->createForm(
                DynamicForm::make([
                    DynamicField::make('static_ip')
                        ->text()
                        ->half()
                        ->label('Static IP (CIDR)')
                        ->placeholder('192.168.1.50/24')
                        ->description('Leave empty to use DHCP. DHCP needs qemu-guest-agent in the template so Vito can find the IP.'),
                    DynamicField::make('gateway')
                        ->text()
                        ->half()
                        ->label('Gateway')
                        ->placeholder('192.168.1.1'),
                ])
            )
            ->defaultUser('root')
            ->provisionTimeout(600)
            ->register();
    }

    /**
     * @return array<int, array{title: string, description?: string, code?: string}>
     */
    private function proxmoxGuide(): array
    {
        return [
            [
                'title' => 'Create an API token',
                'description' => 'Run this in the Proxmox host shell and use vito@pve!vito as the Token ID and the printed value as the secret. If you create the token in the web UI instead, untick Privilege Separation or grant these roles to the token itself.',
                'code' => "pveum user add vito@pve\npveum acl modify / --users vito@pve --roles PVEVMAdmin,PVEDatastoreUser,PVESDNUser,PVEAuditor\npveum user token add vito@pve vito --privsep 0",
            ],
            [
                'title' => 'Download the Ubuntu cloud image',
                'description' => 'Use an official cloud image, not an ISO install. Replace noble with resolute for Ubuntu 26.04 or jammy for Ubuntu 22.04.',
                'code' => "cd /root\nwget https://cloud-images.ubuntu.com/noble/current/noble-server-cloudimg-amd64.img",
            ],
            [
                'title' => 'Add the QEMU guest agent',
                'description' => 'Vito reads a DHCP-assigned IP from the guest agent. Skip this step if you always give servers a static IP.',
                'code' => "apt install -y libguestfs-tools\nvirt-customize -a noble-server-cloudimg-amd64.img --install qemu-guest-agent",
            ],
            [
                'title' => 'Create the template',
                'description' => 'Change vmbr0 and local-lvm if your bridge or storage is named differently. Vito grows the disk to the plan size when it creates a server.',
                'code' => "qm create 9000 --name ubuntu-2404-cloud --memory 2048 --cores 2 --ostype l26 --net0 virtio,bridge=vmbr0 --scsihw virtio-scsi-pci\nqm set 9000 --scsi0 local-lvm:0,import-from=/root/noble-server-cloudimg-amd64.img\nqm set 9000 --ide2 local-lvm:cloudinit --boot order=scsi0 --serial0 socket --vga serial0 --agent 1\nqm template 9000",
            ],
            [
                'title' => 'Connect Vito',
                'description' => 'Use https://your-host:8006 as the API URL, turn off Verify SSL for a self-signed certificate, and enter 9000 as the Ubuntu 24.04 template VMID. Vito must be able to reach the new servers over SSH.',
            ],
        ];
    }
}
