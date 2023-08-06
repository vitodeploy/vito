<?php

return [
    'aws' => [
        'plans' => [
            [
                'title' => '[t2 nano] 512MB RAM - CPU 1 core(s)',
                'value' => 't2.nano',
            ],
            [
                'title' => '[t2 micro] 1024MB RAM - CPU 1 core(s)',
                'value' => 't2.micro',
            ],
            [
                'title' => '[t2 small] 2048MB RAM - CPU 1 core(s)',
                'value' => 't2.small',
            ],
            [
                'title' => '[t2 medium] 4096MB RAM - CPU 2 core(s)',
                'value' => 't2.medium',
            ],
            [
                'title' => '[t2 large] 8192MB RAM - CPU 2 core(s)',
                'value' => 't2.large',
            ],
            [
                'title' => '[t2 xlarge] 16384MB RAM - CPU 4 core(s)',
                'value' => 't2.xlarge',
            ],
            [
                'title' => '[t2 2xlarge] 32768MB RAM - CPU 8 core(s)',
                'value' => 't2.2xlarge',
            ],
            [
                'title' => '[t3a nano] 512MB RAM - CPU 2 core(s)',
                'value' => 't3a.nano',
            ],
            [
                'title' => '[t3a micro] 1024MB RAM - CPU 2 core(s)',
                'value' => 't3a.micro',
            ],
            [
                'title' => '[t3a small] 2048MB RAM - CPU 2 core(s)',
                'value' => 't3a.small',
            ],
            [
                'title' => '[t3a medium] 4096MB RAM - CPU 2 core(s)',
                'value' => 't3a.medium',
            ],
            [
                'title' => '[t3a large] 8192MB RAM - CPU 2 core(s)',
                'value' => 't3a.large',
            ],
            [
                'title' => '[t3a xlarge] 16384MB RAM - CPU 4 core(s)',
                'value' => 't3a.xlarge',
            ],
        ],
        'regions' => [
            [
                'title' => 'US East (N. Virginia) (us-east-1)',
                'value' => 'us-east-1',
            ],
            [
                'title' => 'US East (Ohio) (us-east-2)',
                'value' => 'us-east-2',
            ],
            [
                'title' => 'US West (N. California) (us-west-1)',
                'value' => 'us-west-1',
            ],
            [
                'title' => 'US West (Oregon) (us-west-2)',
                'value' => 'us-west-2',
            ],
            [
                'title' => 'Asia Pacific (Hong Kong) (ap-east-1)',
                'value' => 'ap-east-1',
            ],
            [
                'title' => 'Asia Pacific (Mumbai) (ap-south-1)',
                'value' => 'ap-south-1',
            ],
            [
                'title' => 'Asia Pacific (Singapore) (ap-southeast-1',
                'value' => 'ap-southeast-1',
            ],
            [
                'title' => 'Asia Pacific (Seoul) (ap-northeast-2)',
                'value' => 'ap-northeast-2',
            ],
            [
                'title' => 'Asia Pacific (Tokyo) (ap-northeast-1)',
                'value' => 'ap-northeast-1',
            ],
            [
                'title' => 'Asia Pacific (Sydney) (ap-southeast-2)',
                'value' => 'ap-southeast-2',
            ],
            [
                'title' => 'Canada (Central) (ca-central-1)',
                'value' => 'ca-central-1',
            ],
            [
                'title' => 'Europe (Frankfurt) (eu-central-1)',
                'value' => 'eu-central-1',
            ],
            [
                'title' => 'Europe (Ireland) (eu-west-1)',
                'value' => 'eu-west-1',
            ],
            [
                'title' => 'Europe (London) (eu-west-2)',
                'value' => 'eu-west-2',
            ],
            [
                'title' => 'Europe (Paris) (eu-west-3)',
                'value' => 'eu-west-3',
            ],
            [
                'title' => 'Europe (Milan) (eu-south-1)',
                'value' => 'eu-south-1',
            ],
            [
                'title' => 'Europe (Stockholm) (eu-north-1)',
                'value' => 'eu-north-1',
            ],
            [
                'title' => 'Middle East (Bahrain) (me-south-1)',
                'value' => 'me-south-1',
            ],
            [
                'title' => 'South America (São Paulo) (sa-east-1)',
                'value' => 'sa-east-1',
            ],
            [
                'title' => 'Africa (Cape Town) (af-south-1)',
                'value' => 'af-south-1',
            ],
        ],
        'images' => [
            'us-east-1' => [
                'ubuntu_18' => 'ami-0279c3b3186e54acd',
                'ubuntu_20' => 'ami-083654bd07b5da81d',
            ],
            'us-east-2' => [
                'ubuntu_18' => 'ami-020db2c14939a8efb',
                'ubuntu_20' => 'ami-0629230e074c580f2',
            ],
            'us-west-1' => [
                'ubuntu_18' => 'ami-083f68207d3376798',
                'ubuntu_20' => 'ami-053ac55bdcfe96e85',
            ],
            'us-west-2' => [
                'ubuntu_18' => 'ami-09889d8d54f9e0a0e',
                'ubuntu_20' => 'ami-036d46416a34a611c',
            ],
            'ap-east-1' => [
                'ubuntu_18' => 'ami-032c0a4bd39a5772c',
                'ubuntu_20' => 'ami-0a9c1cc3697104990',
            ],
            'ap-south-1' => [
                'ubuntu_18' => 'ami-00782a7608c7fc226',
                'ubuntu_20' => 'ami-0567e0d2b4b2169ae',
            ],
            'ap-northeast-1' => [
                'ubuntu_18' => 'ami-085e9421f80dbe728',
                'ubuntu_20' => 'ami-036d0684fc96830ca',
            ],
            'ap-northeast-2' => [
                'ubuntu_18' => 'ami-0252a84eb1d66c2a0',
                'ubuntu_20' => 'ami-0f8b8babb98cc66d0',
            ],
            'ap-southeast-1' => [
                'ubuntu_18' => 'ami-0907c2c44ea451f84',
                'ubuntu_20' => 'ami-0fed77069cd5a6d6c',
            ],
            'ap-southeast-2' => [
                'ubuntu_18' => 'ami-00abf0511a7f4cee5',
                'ubuntu_20' => 'ami-0bf8b986de7e3c7ce',
            ],
            'ca-central-1' => [
                'ubuntu_18' => 'ami-0e471deaa43652c4a',
                'ubuntu_20' => 'ami-0bb84e7329f4fa1f7',
            ],
            'eu-central-1' => [
                'ubuntu_18' => 'ami-00d5e377dd7fad751',
                'ubuntu_20' => 'ami-0a49b025fffbbdac6',
            ],
            'eu-west-1' => [
                'ubuntu_18' => 'ami-095b735dce49535b5',
                'ubuntu_20' => 'ami-08edbb0e85d6a0a07',
            ],
            'eu-west-2' => [
                'ubuntu_18' => 'ami-008485ca60c91a0f3',
                'ubuntu_20' => 'ami-0fdf70ed5c34c5f52',
            ],
            'eu-west-3' => [
                'ubuntu_18' => 'ami-0df7d9cc2767d16cd',
                'ubuntu_20' => 'ami-06d79c60d7454e2af',
            ],
            'eu-south-1' => [
                'ubuntu_18' => 'ami-09f165dd6bd167be5',
                'ubuntu_20' => 'ami-0f8ce9c417115413d',
            ],
            'eu-north-1' => [
                'ubuntu_18' => 'ami-038904f9024f34a0c',
                'ubuntu_20' => 'ami-0bd9c26722573e69b',
            ],
            'me-south-1' => [
                'ubuntu_18' => 'ami-0ef669c57b73af73b',
                'ubuntu_20' => 'ami-0b4946d7420c44be4',
            ],
            'sa-east-1' => [
                'ubuntu_18' => 'ami-0ed2b3edeb28afa59',
                'ubuntu_20' => 'ami-0e66f5495b4efdd0f',
            ],
            'af-south-1' => [
                'ubuntu_18' => 'ami-0191bb2cf509687ee',
                'ubuntu_20' => 'ami-0ff86122fd4ad7208',
            ],
        ],
    ],
    'linode' => [
        'plans' => [
            [
                'title' => 'Nanode 1GB',
                'value' => 'g6-nanode-1',
            ],
            [
                'title' => 'Linode 2GB',
                'value' => 'g6-standard-1',
            ],
            [
                'title' => 'Linode 4GB',
                'value' => 'g6-standard-2',
            ],
            [
                'title' => 'Linode 8GB',
                'value' => 'g6-standard-4',
            ],
            [
                'title' => 'Linode 16GB',
                'value' => 'g6-standard-6',
            ],
            [
                'title' => 'Linode 32GB',
                'value' => 'g6-standard-8',
            ],
            [
                'title' => 'Linode 64GB',
                'value' => 'g6-standard-16',
            ],
            [
                'title' => 'Linode 96GB',
                'value' => 'g6-standard-20',
            ],
            [
                'title' => 'Linode 128GB',
                'value' => 'g6-standard-24',
            ],
            [
                'title' => 'Linode 192GB',
                'value' => 'g6-standard-32',
            ],
            [
                'title' => 'Linode 24GB',
                'value' => 'g7-highmem-1',
            ],
            [
                'title' => 'Linode 48GB',
                'value' => 'g7-highmem-2',
            ],
            [
                'title' => 'Linode 90GB',
                'value' => 'g7-highmem-4',
            ],
            [
                'title' => 'Linode 150GB',
                'value' => 'g7-highmem-8',
            ],
            [
                'title' => 'Linode 300GB',
                'value' => 'g7-highmem-16',
            ],
            [
                'title' => 'Dedicated 4GB',
                'value' => 'g6-dedicated-2',
            ],
            [
                'title' => 'Dedicated 8GB',
                'value' => 'g6-dedicated-4',
            ],
            [
                'title' => 'Dedicated 16GB',
                'value' => 'g6-dedicated-8',
            ],
            [
                'title' => 'Dedicated 32GB',
                'value' => 'g6-dedicated-16',
            ],
            [
                'title' => 'Dedicated 64GB',
                'value' => 'g6-dedicated-32',
            ],
            [
                'title' => 'Dedicated 96GB',
                'value' => 'g6-dedicated-48',
            ],
            [
                'title' => 'Dedicated 128GB',
                'value' => 'g6-dedicated-50',
            ],
            [
                'title' => 'Dedicated 256GB',
                'value' => 'g6-dedicated-56',
            ],
            [
                'title' => 'Dedicated 512GB',
                'value' => 'g6-dedicated-64',
            ],
            [
                'title' => 'Dedicated 32GB + RTX6000 GPU x1',
                'value' => 'g1-gpu-rtx6000-1',
            ],
            [
                'title' => 'Dedicated 64GB + RTX6000 GPU x2',
                'value' => 'g1-gpu-rtx6000-2',
            ],
            [
                'title' => 'Dedicated 96GB + RTX6000 GPU x3',
                'value' => 'g1-gpu-rtx6000-3',
            ],
            [
                'title' => 'Dedicated 128GB + RTX6000 GPU x4',
                'value' => 'g1-gpu-rtx6000-4',
            ],
        ],
        'regions' => [
            [
                'title' => 'ap-west - India',
                'value' => 'ap-west',
            ],
            [
                'title' => 'ca-central - Canada',
                'value' => 'ca-central',
            ],
            [
                'title' => 'ap-southeast - Australia',
                'value' => 'ap-southeast',
            ],
            [
                'title' => 'us-central - United States',
                'value' => 'us-central',
            ],
            [
                'title' => 'us-west - United States',
                'value' => 'us-west',
            ],
            [
                'title' => 'us-southeast - United States',
                'value' => 'us-southeast',
            ],
            [
                'title' => 'us-east - United States',
                'value' => 'us-east',
            ],
            [
                'title' => 'eu-west - United Kingdom',
                'value' => 'eu-west',
            ],
            [
                'title' => 'ap-south - Singapore',
                'value' => 'ap-south',
            ],
            [
                'title' => 'eu-central - Germany',
                'value' => 'eu-central',
            ],
            [
                'title' => 'ap-northeast - Japan',
                'value' => 'ap-northeast',
            ],
        ],
        'images' => [
            'ubuntu_18' => 'linode/ubuntu18.04',
            'ubuntu_20' => 'linode/ubuntu20.04',
            'ubuntu_22' => 'linode/ubuntu22.04',
        ],
    ],
    'digitalocean' => [
        'plans' => [
            [
                'title' => '1024MB RAM - CPU 1 core(s) - Disk: 25GB (s-1vcpu-1gb)',
                'value' => 's-1vcpu-1gb',
            ],
            [
                'title' => '1024MB RAM - CPU 1 core(s) - Disk: 25GB (s-1vcpu-1gb-amd)',
                'value' => 's-1vcpu-1gb-amd',
            ],
            [
                'title' => '1024MB RAM - CPU 1 core(s) - Disk: 25GB (s-1vcpu-1gb-intel)',
                'value' => 's-1vcpu-1gb-intel',
            ],
            [
                'title' => '2048MB RAM - CPU 1 core(s) - Disk: 50GB (s-1vcpu-2gb)',
                'value' => 's-1vcpu-2gb',
            ],
            [
                'title' => '2048MB RAM - CPU 1 core(s) - Disk: 50GB (s-1vcpu-2gb-amd)',
                'value' => 's-1vcpu-2gb-amd',
            ],
            [
                'title' => '2048MB RAM - CPU 1 core(s) - Disk: 50GB (s-1vcpu-2gb-intel)',
                'value' => 's-1vcpu-2gb-intel',
            ],
            [
                'title' => '2048MB RAM - CPU 2 core(s) - Disk: 60GB (s-2vcpu-2gb)',
                'value' => 's-2vcpu-2gb',
            ],
            [
                'title' => '2048MB RAM - CPU 2 core(s) - Disk: 60GB (s-2vcpu-2gb-amd)',
                'value' => 's-2vcpu-2gb-amd',
            ],
            [
                'title' => '2048MB RAM - CPU 2 core(s) - Disk: 60GB (s-2vcpu-2gb-intel)',
                'value' => 's-2vcpu-2gb-intel',
            ],
            [
                'title' => '4096MB RAM - CPU 2 core(s) - Disk: 80GB (s-2vcpu-4gb)',
                'value' => 's-2vcpu-4gb',
            ],
            [
                'title' => '4096MB RAM - CPU 2 core(s) - Disk: 80GB (s-2vcpu-4gb-amd)',
                'value' => 's-2vcpu-4gb-amd',
            ],
            [
                'title' => '4096MB RAM - CPU 2 core(s) - Disk: 80GB (s-2vcpu-4gb-intel)',
                'value' => 's-2vcpu-4gb-intel',
            ],
            [
                'title' => '8192MB RAM - CPU 4 core(s) - Disk: 160GB (s-4vcpu-8gb)',
                'value' => 's-4vcpu-8gb',
            ],
            [
                'title' => '8192MB RAM - CPU 4 core(s) - Disk: 160GB (s-4vcpu-8gb-amd)',
                'value' => 's-4vcpu-8gb-amd',
            ],
            [
                'title' => '8192MB RAM - CPU 4 core(s) - Disk: 160GB (s-4vcpu-8gb-intel)',
                'value' => 's-4vcpu-8gb-intel',
            ],
            [
                'title' => '16384MB RAM - CPU 8 core(s) - Disk: 320GB (s-8vcpu-16gb)',
                'value' => 's-8vcpu-16gb',
            ],
            [
                'title' => '4096MB RAM - CPU 2 core(s) - Disk: 25GB (c-2)',
                'value' => 'c-2',
            ],
            [
                'title' => '4096MB RAM - CPU 2 core(s) - Disk: 50GB (c2-2vcpu-4gb)',
                'value' => 'c2-2vcpu-4gb',
            ],
            [
                'title' => '8192MB RAM - CPU 2 core(s) - Disk: 25GB (g-2vcpu-8gb)',
                'value' => 'g-2vcpu-8gb',
            ],
            [
                'title' => '8192MB RAM - CPU 2 core(s) - Disk: 50GB (gd-2vcpu-8gb)',
                'value' => 'gd-2vcpu-8gb',
            ],
        ],
        'regions' => [
            [
                'title' => 'New York 1',
                'value' => 'nyc1',
            ],
            [
                'title' => 'Amsterdam 2',
                'value' => 'ams2',
            ],
            [
                'title' => 'Singapore 1',
                'value' => 'sgp1',
            ],
            [
                'title' => 'London 1',
                'value' => 'lon1',
            ],
            [
                'title' => 'New York 3',
                'value' => 'nyc3',
            ],
            [
                'title' => 'Amsterdam 3',
                'value' => 'ams3',
            ],
            [
                'title' => 'Frankfurt 1',
                'value' => 'fra1',
            ],
            [
                'title' => 'Toronto 1',
                'value' => 'tor1',
            ],
            [
                'title' => 'Bangalore 1',
                'value' => 'blr1',
            ],
            [
                'title' => 'San Francisco 3',
                'value' => 'sfo3',
            ],
        ],
        'images' => [
            'ubuntu_18' => '112929540',
            'ubuntu_20' => '112929454',
            'ubuntu_22' => '129211873',
        ],
    ],
    'vultr' => [
        'plans' => [
            [
                'title' => '1 CPU - 1024MB Ram - 25GB Disk',
                'value' => 'vc2-1c-1gb',
            ],
            [
                'title' => '1 CPU - 2048MB Ram - 55GB Disk',
                'value' => 'vc2-1c-2gb',
            ],
            [
                'title' => '2 CPU - 4096MB Ram - 80GB Disk',
                'value' => 'vc2-2c-4gb',
            ],
            [
                'title' => '4 CPU - 8192MB Ram - 160GB Disk',
                'value' => 'vc2-4c-8gb',
            ],
            [
                'title' => '6 CPU - 16384MB Ram - 320GB Disk',
                'value' => 'vc2-6c-16gb',
            ],
            [
                'title' => '8 CPU - 32768MB Ram - 640GB Disk',
                'value' => 'vc2-8c-32gb',
            ],
            [
                'title' => '16 CPU - 65536MB Ram - 1280GB Disk',
                'value' => 'vc2-16c-64gb',
            ],
            [
                'title' => '24 CPU - 98304MB Ram - 1600GB Disk',
                'value' => 'vc2-24c-96gb',
            ],
            [
                'title' => '2 CPU - 8192MB Ram - 110GB Disk',
                'value' => 'vdc-2vcpu-8gb',
            ],
            [
                'title' => '4 CPU - 16384MB Ram - 110GB Disk',
                'value' => 'vdc-4vcpu-16gb',
            ],
            [
                'title' => '6 CPU - 24576MB Ram - 110GB Disk',
                'value' => 'vdc-6vcpu-24gb',
            ],
            [
                'title' => '8 CPU - 32768MB Ram - 110GB Disk',
                'value' => 'vdc-8vcpu-32gb',
            ],
            [
                'title' => '1 CPU - 1024MB Ram - 32GB Disk',
                'value' => 'vhf-1c-1gb',
            ],
            [
                'title' => '1 CPU - 2048MB Ram - 64GB Disk',
                'value' => 'vhf-1c-2gb',
            ],
            [
                'title' => '2 CPU - 2048MB Ram - 80GB Disk',
                'value' => 'vhf-2c-2gb',
            ],
            [
                'title' => '2 CPU - 4096MB Ram - 128GB Disk',
                'value' => 'vhf-2c-4gb',
            ],
            [
                'title' => '3 CPU - 8192MB Ram - 256GB Disk',
                'value' => 'vhf-3c-8gb',
            ],
            [
                'title' => '4 CPU - 16384MB Ram - 384GB Disk',
                'value' => 'vhf-4c-16gb',
            ],
            [
                'title' => '6 CPU - 24576MB Ram - 448GB Disk',
                'value' => 'vhf-6c-24gb',
            ],
            [
                'title' => '8 CPU - 32768MB Ram - 512GB Disk',
                'value' => 'vhf-8c-32gb',
            ],
            [
                'title' => '12 CPU - 49152MB Ram - 768GB Disk',
                'value' => 'vhf-12c-48gb',
            ],
        ],
        'regions' => [
            [
                'title' => 'Europe - Amsterdam',
                'value' => 'ams',
            ],
            [
                'title' => 'North America - Atlanta',
                'value' => 'atl',
            ],
            [
                'title' => 'Europe - Paris',
                'value' => 'cdg',
            ],
            [
                'title' => 'North America - Dallas',
                'value' => 'dfw',
            ],
            [
                'title' => 'North America - New Jersey',
                'value' => 'ewr',
            ],
            [
                'title' => 'Europe - Frankfurt',
                'value' => 'fra',
            ],
            [
                'title' => 'Asia - Seoul',
                'value' => 'icn',
            ],
            [
                'title' => 'North America - Los Angeles',
                'value' => 'lax',
            ],
            [
                'title' => 'Europe - London',
                'value' => 'lhr',
            ],
            [
                'title' => 'North America - Mexico City',
                'value' => 'mex',
            ],
            [
                'title' => 'North America - Miami',
                'value' => 'mia',
            ],
            [
                'title' => 'Asia - Tokyo',
                'value' => 'nrt',
            ],
            [
                'title' => 'North America - Chicago',
                'value' => 'ord',
            ],
            [
                'title' => 'North America - Seattle',
                'value' => 'sea',
            ],
            [
                'title' => 'Asia - Singapore',
                'value' => 'sgp',
            ],
            [
                'title' => 'North America - Silicon Valley',
                'value' => 'sjc',
            ],
            [
                'title' => 'Europe - Stockholm',
                'value' => 'sto',
            ],
            [
                'title' => 'Australia - Sydney',
                'value' => 'syd',
            ],
            [
                'title' => 'North America - Toronto',
                'value' => 'yto',
            ],
        ],
        'images' => [
            'ubuntu_18' => '270',
            'ubuntu_20' => '387',
            'ubuntu_22' => '1743',
        ],
    ],
    'hetzner' => [
        'plans' => [
            [
                'title' => 'CX11 - 1 Cores - 2 Memory - 20 Disk',
                'value' => 'cx11',
            ],
            [
                'title' => 'CX21 - 2 Cores - 4 Memory - 40 Disk',
                'value' => 'cx21',
            ],
            [
                'title' => 'CX31 - 2 Cores - 8 Memory - 80 Disk',
                'value' => 'cx31',
            ],
            [
                'title' => 'CX41 - 4 Cores - 16 Memory - 160 Disk',
                'value' => 'cx41',
            ],
            [
                'title' => 'CX51 - 8 Cores - 32 Memory - 240 Disk',
                'value' => 'cx51',
            ],
            [
                'title' => 'CCX11 Dedicated CPU - 2 Cores - 8 Memory - 80 Disk',
                'value' => 'ccx11',
            ],
            [
                'title' => 'CCX21 Dedicated CPU - 4 Cores - 16 Memory - 160 Disk',
                'value' => 'ccx21',
            ],
            [
                'title' => 'CCX31 Dedicated CPU - 8 Cores - 32 Memory - 240 Disk',
                'value' => 'ccx31',
            ],
            [
                'title' => 'CCX41 Dedicated CPU - 16 Cores - 64 Memory - 360 Disk',
                'value' => 'ccx41',
            ],
            [
                'title' => 'CCX51 Dedicated CPU - 32 Cores - 128 Memory - 600 Disk',
                'value' => 'ccx51',
            ],
            [
                'title' => 'CPX 11 - 2 Cores - 2 Memory - 40 Disk',
                'value' => 'cpx11',
            ],
            [
                'title' => 'CPX 21 - 3 Cores - 4 Memory - 80 Disk',
                'value' => 'cpx21',
            ],
            [
                'title' => 'CPX 31 - 4 Cores - 8 Memory - 160 Disk',
                'value' => 'cpx31',
            ],
            [
                'title' => 'CPX 41 - 8 Cores - 16 Memory - 240 Disk',
                'value' => 'cpx41',
            ],
            [
                'title' => 'CPX 51 - 16 Cores - 32 Memory - 360 Disk',
                'value' => 'cpx51',
            ],
            [
                'title' => 'CCX12 Dedicated CPU - 2 Cores - 8 Memory - 80 Disk',
                'value' => 'ccx12',
            ],
            [
                'title' => 'CCX22 Dedicated CPU - 4 Cores - 16 Memory - 160 Disk',
                'value' => 'ccx22',
            ],
            [
                'title' => 'CCX32 Dedicated CPU - 8 Cores - 32 Memory - 240 Disk',
                'value' => 'ccx32',
            ],
            [
                'title' => 'CCX42 Dedicated CPU - 16 Cores - 64 Memory - 360 Disk',
                'value' => 'ccx42',
            ],
            [
                'title' => 'CCX52 Dedicated CPU - 32 Cores - 128 Memory - 600 Disk',
                'value' => 'ccx52',
            ],
            [
                'title' => 'CCX62 Dedicated CPU - 48 Cores - 192 Memory - 960 Disk',
                'value' => 'ccx62',
            ],
            [
                'title' => 'CAX11 - 2 Cores - 4 Memory - 40 Disk',
                'value' => 'cax11',
            ],
            [
                'title' => 'CAX21 - 4 Cores - 8 Memory - 80 Disk',
                'value' => 'cax21',
            ],
            [
                'title' => 'CAX31 - 8 Cores - 16 Memory - 160 Disk',
                'value' => 'cax31',
            ],
            [
                'title' => 'CAX41 - 16 Cores - 32 Memory - 320 Disk',
                'value' => 'cax41',
            ],
        ],
        'regions' => [
            [
                'title' => 'DE - Falkenstein',
                'value' => 'fsn1',
            ],
            [
                'title' => 'DE - Nuremberg',
                'value' => 'nbg1',
            ],
            [
                'title' => 'FI - Helsinki',
                'value' => 'hel1',
            ],
            [
                'title' => 'US - Ashburn, VA',
                'value' => 'ash',
            ],
            [
                'title' => 'US - Hillsboro, OR',
                'value' => 'hil',
            ],
        ],
        'images' => [
            'ubuntu_18' => 'ubuntu-18.04',
            'ubuntu_20' => 'ubuntu-20.04',
            'ubuntu_22' => 'ubuntu-22.04',
        ],
    ],
];
