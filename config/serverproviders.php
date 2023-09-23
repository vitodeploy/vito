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
            'af-south-1' => [
                'ubuntu_20' => 'ami-03684d4c2541e5333',
                'ubuntu_22' => 'ami-05759acc7d8973892',
            ],
            'ap-east-1' => [
                'ubuntu_20' => 'ami-0b19a97bf326b4931',
                'ubuntu_22' => 'ami-03490b1b7425e5fe3',
            ],
            'ap-northeast-1' => [
                'ubuntu_20' => 'ami-0e25df74d27e028e6',
                'ubuntu_22' => 'ami-09a81b370b76de6a2',
            ],
            'ap-northeast-2' => [
                'ubuntu_20' => 'ami-003a709e1e4ce3729',
                'ubuntu_22' => 'ami-086cae3329a3f7d75',
            ],
            'ap-northeast-3' => [
                'ubuntu_20' => 'ami-06c1367bd83de7d47',
                'ubuntu_22' => 'ami-0690c54203f5f67da',
            ],
            'ap-south-1' => [
                'ubuntu_20' => 'ami-0b88997c830e88c76',
                'ubuntu_22' => 'ami-0287a05f0ef0e9d9a',
            ],
            'ap-south-2' => [
                'ubuntu_20' => 'ami-049e2ae605332dba6',
                'ubuntu_22' => 'ami-06fe902e167e67d33',
            ],
            'ap-southeast-1' => [
                'ubuntu_20' => 'ami-0a6461ddb52e9db63',
                'ubuntu_22' => 'ami-078c1149d8ad719a7',
            ],
            'ap-southeast-2' => [
                'ubuntu_20' => 'ami-0a9fb81cc3289919c',
                'ubuntu_22' => 'ami-0df4b2961410d4cff',
            ],
            'ap-southeast-3' => [
                'ubuntu_20' => 'ami-05ee5bed682a3fff0',
                'ubuntu_22' => 'ami-0fb6d1fdeeea10488',
            ],
            'ap-southeast-4' => [
                'ubuntu_20' => 'ami-02f9759882b112414',
                'ubuntu_22' => 'ami-043a030d3eeabec75',
            ],
            'ca-central-1' => [
                'ubuntu_20' => 'ami-0daaea212e620de87',
                'ubuntu_22' => 'ami-06873c81b882339ac',
            ],
            'cn-north-1' => [
                'ubuntu_20' => 'ami-0c8bcac1fe3389a72',
                'ubuntu_22' => 'ami-0728a1a4cc9e07753',
            ],
            'cn-northwest-1' => [
                'ubuntu_20' => 'ami-0415bfb3ea62e17c0',
                'ubuntu_22' => 'ami-05529cf859783e600',
            ],
            'eu-central-1' => [
                'ubuntu_20' => 'ami-0b369586722023326',
                'ubuntu_22' => 'ami-06dd92ecc74fdfb36',
            ],
            'eu-central-2' => [
                'ubuntu_20' => 'ami-070c78d5ed65f11c8',
                'ubuntu_22' => 'ami-07cf963e6321c9e6a',
            ],
            'eu-north-1' => [
                'ubuntu_20' => 'ami-0c5863072fc83557e',
                'ubuntu_22' => 'ami-0fe8bec493a81c7da',
            ],
            'eu-south-1' => [
                'ubuntu_20' => 'ami-0966ff128f1497260',
                'ubuntu_22' => 'ami-0b03947fd0ce0eed2',
            ],
            'eu-south-2' => [
                'ubuntu_20' => 'ami-087296a5b46cb95ce',
                'ubuntu_22' => 'ami-03486abd2962c176f',
            ],
            'eu-west-1' => [
                'ubuntu_20' => 'ami-0e3e7f215a53e2a86',
                'ubuntu_22' => 'ami-0694d931cee176e7d',
            ],
            'eu-west-2' => [
                'ubuntu_20' => 'ami-0b22eee5ba6bb6772',
                'ubuntu_22' => 'ami-0505148b3591e4c07',
            ],
            'eu-west-3' => [
                'ubuntu_20' => 'ami-0f14fa1f9c69f4111',
                'ubuntu_22' => 'ami-00983e8a26e4c9bd9',
            ],
            'il-central-1' => [
                'ubuntu_20' => 'ami-0703881563bf5fab7',
                'ubuntu_22' => 'ami-03869c813f5a2e20c',
            ],
            'me-central-1' => [
                'ubuntu_20' => 'ami-04a5bde3b044c7c21',
                'ubuntu_22' => 'ami-02168d82d5c12118f',
            ],
            'me-south-1' => [
                'ubuntu_20' => 'ami-0165b692f5714e330',
                'ubuntu_22' => 'ami-0f8d2a6080634ee69',
            ],
            'sa-east-1' => [
                'ubuntu_20' => 'ami-095ca107fb46b81e6',
                'ubuntu_22' => 'ami-0b6c2d49148000cd5',
            ],
            'us-east-1' => [
                'ubuntu_20' => 'ami-0fe0238291c8e3f07',
                'ubuntu_22' => 'ami-0fc5d935ebf8bc3bc',
            ],
            'us-east-2' => [
                'ubuntu_20' => 'ami-0b6968e5c7117349a',
                'ubuntu_22' => 'ami-0e83be366243f524a',
            ],
            'us-west-1' => [
                'ubuntu_20' => 'ami-092efbcc9a2d2be8a',
                'ubuntu_22' => 'ami-0cbd40f694b804622',
            ],
            'us-west-2' => [
                'ubuntu_20' => 'ami-0a55cdf919d10eac9',
                'ubuntu_22' => 'ami-0efcece6bed30fd98',
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
