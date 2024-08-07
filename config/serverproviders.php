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
                'title' => 'US East (Ohio)',
                'value' => 'us-east-2',
            ],
            [
                'title' => 'US East (Virginia)',
                'value' => 'us-east-1',
            ],
            [
                'title' => 'US West (N. California)',
                'value' => 'us-west-1',
            ],
            [
                'title' => 'US West (Oregon)',
                'value' => 'us-west-2',
            ],
            [
                'title' => 'Africa (Cape Town)',
                'value' => 'af-south-1',
            ],
            [
                'title' => 'Asia Pacific (Hong Kong)',
                'value' => 'ap-east-1',
            ],
            [
                'title' => 'Asia Pacific (Hyderabad)',
                'value' => 'ap-south-2',
            ],
            [
                'title' => 'Asia Pacific (Jakarta)',
                'value' => 'ap-southeast-3',
            ],
            [
                'title' => 'Asia Pacific (Melbourne)',
                'value' => 'ap-southeast-4',
            ],
            [
                'title' => 'Asia Pacific (Mumbai)',
                'value' => 'ap-south-1',
            ],
            [
                'title' => 'Asia Pacific (Osaka)',
                'value' => 'ap-northeast-3',
            ],
            [
                'title' => 'Asia Pacific (Seoul)',
                'value' => 'ap-northeast-2',
            ],
            [
                'title' => 'Asia Pacific (Singapore)',
                'value' => 'ap-southeast-1',
            ],
            [
                'title' => 'Asia Pacific (Sydney)',
                'value' => 'ap-southeast-2',
            ],
            [
                'title' => 'Asia Pacific (Tokyo)',
                'value' => 'ap-northeast-1',
            ],
            [
                'title' => 'Canada (Central)',
                'value' => 'ca-central-1',
            ],
            [
                'title' => 'Canada West (Calgary)',
                'value' => 'ca-west-1',
            ],
            [
                'title' => 'Europe (Frankfurt)',
                'value' => 'eu-central-1',
            ],
            [
                'title' => 'Europe (Ireland)',
                'value' => 'eu-west-1',
            ],
            [
                'title' => 'Europe (London)',
                'value' => 'eu-west-2',
            ],
            [
                'title' => 'Europe (Milan)',
                'value' => 'eu-south-1',
            ],
            [
                'title' => 'Europe (Paris)',
                'value' => 'eu-west-3',
            ],
            [
                'title' => 'Europe (Spain)',
                'value' => 'eu-south-2',
            ],
            [
                'title' => 'Europe (Stockholm)',
                'value' => 'eu-north-1',
            ],
            [
                'title' => 'Europe (Zurich)',
                'value' => 'eu-central-2',
            ],
            [
                'title' => 'Israel (Tel Aviv)',
                'value' => 'il-central-1',
            ],
            [
                'title' => 'Middle East (Bahrain)',
                'value' => 'me-south-1',
            ],
            [
                'title' => 'Middle East (UAE)',
                'value' => 'me-central-1',
            ],
            [
                'title' => 'South America (São Paulo)',
                'value' => 'sa-east-1',
            ],
        ],
        'images' => [
            'eu-south-2' => [
                'ubuntu_24' => 'ami-0a50f993202fe4f22',
                'ubuntu_22' => 'ami-043e9941c6aec0f52',
                'ubuntu_20' => 'ami-086f353893612e446',
            ],
            'eu-west-1' => [
                'ubuntu_24' => 'ami-0776c814353b4814d',
                'ubuntu_22' => 'ami-0d0fa503c811361ab',
                'ubuntu_20' => 'ami-0008aa5cb0cde3400',
            ],
            'af-south-1' => [
                'ubuntu_24' => 'ami-0bfda59e8f84ff5ed',
                'ubuntu_22' => 'ami-0d06a4031539a9be6',
                'ubuntu_20' => 'ami-0ea465fccfaf199ce',
            ],
            'eu-west-2' => [
                'ubuntu_24' => 'ami-053a617c6207ecc7b',
                'ubuntu_22' => 'ami-0eb5c35d7b89f3488',
                'ubuntu_20' => 'ami-0608dbf22649c0159',
            ],
            'eu-south-1' => [
                'ubuntu_24' => 'ami-0355c99d0faba8847',
                'ubuntu_22' => 'ami-0bdcf995dcfebf29c',
                'ubuntu_20' => 'ami-034ea9dc86027e603',
            ],
            'ap-south-1' => [
                'ubuntu_24' => 'ami-0f58b397bc5c1f2e8',
                'ubuntu_22' => 'ami-0f16c6c3de733b474',
                'ubuntu_20' => 'ami-02f829375c976f810',
            ],
            'il-central-1' => [
                'ubuntu_24' => 'ami-04a4b28d712600827',
                'ubuntu_22' => 'ami-09cd8eea397932e88',
                'ubuntu_20' => 'ami-03988803bd4e18212',
            ],
            'eu-north-1' => [
                'ubuntu_24' => 'ami-0705384c0b33c194c',
                'ubuntu_22' => 'ami-0fff1012fc5cb9f25',
                'ubuntu_20' => 'ami-07cca21629288f454',
            ],
            'me-central-1' => [
                'ubuntu_24' => 'ami-048798fd481c4c791',
                'ubuntu_22' => 'ami-042fcc4c33a3b6429',
                'ubuntu_20' => 'ami-0f98fff9d77968c80',
            ],
            'ca-central-1' => [
                'ubuntu_24' => 'ami-0c4596ce1e7ae3e68',
                'ubuntu_22' => 'ami-04fea581fe25e2675',
                'ubuntu_20' => 'ami-05690acfbddfbeaf6',
            ],
            'eu-west-3' => [
                'ubuntu_24' => 'ami-00ac45f3035ff009e',
                'ubuntu_22' => 'ami-0b020d95f579c8f43',
                'ubuntu_20' => 'ami-0130b7d3ec1d07e4f',
            ],
            'ap-south-2' => [
                'ubuntu_24' => 'ami-008616ec4a2c6975e',
                'ubuntu_22' => 'ami-088e75eecea53e53e',
                'ubuntu_20' => 'ami-0688d182e7c22ec3f',
            ],
            'ca-west-1' => [
                'ubuntu_24' => 'ami-07022089d2e36ace0',
                'ubuntu_22' => 'ami-02e22cefcad05a835',
                'ubuntu_20' => 'ami-03890126b7675fac8',
            ],
            'eu-central-1' => [
                'ubuntu_24' => 'ami-01e444924a2233b07',
                'ubuntu_22' => 'ami-01a93368cab494eb5',
                'ubuntu_20' => 'ami-07fd6b7604806e876',
            ],
            'me-south-1' => [
                'ubuntu_24' => 'ami-087f3ec3fdda67295',
                'ubuntu_22' => 'ami-03ae386fab11fa0a1',
                'ubuntu_20' => 'ami-0f65a186b3552f348',
            ],
            'ap-northeast-1' => [
                'ubuntu_24' => 'ami-01bef798938b7644d',
                'ubuntu_22' => 'ami-08e32db9e33e28876',
                'ubuntu_20' => 'ami-0ed286a950292f370',
            ],
            'ap-southeast-1' => [
                'ubuntu_24' => 'ami-003c463c8207b4dfa',
                'ubuntu_22' => 'ami-084cab24460184bd3',
                'ubuntu_20' => 'ami-081ee02c4cdf3917c',
            ],
            'us-west-1' => [
                'ubuntu_24' => 'ami-08012c0a9ee8e21c4',
                'ubuntu_22' => 'ami-023f8bebe991375fd',
                'ubuntu_20' => 'ami-0344f34a6875de16a',
            ],
            'ap-southeast-3' => [
                'ubuntu_24' => 'ami-00c31062c5966e820',
                'ubuntu_22' => 'ami-0fd547652d1673e30',
                'ubuntu_20' => 'ami-0699dddffd3542faf',
            ],
            'ap-northeast-2' => [
                'ubuntu_24' => 'ami-0e6f2b2fa0ca704d0',
                'ubuntu_22' => 'ami-0720c7fcba4b88b36',
                'ubuntu_20' => 'ami-03ec7d02334d21d49',
            ],
            'ap-southeast-2' => [
                'ubuntu_24' => 'ami-080660c9757080771',
                'ubuntu_22' => 'ami-0d9d3b991cfa8ac6e',
                'ubuntu_20' => 'ami-06c7a70c38594fef6',
            ],
            'us-east-1' => [
                'ubuntu_24' => 'ami-04b70fa74e45c3917',
                'ubuntu_22' => 'ami-0cfa2ad4242c3168d',
                'ubuntu_20' => 'ami-0e3a6d8ff4c8fe246',
            ],
            'us-west-2' => [
                'ubuntu_24' => 'ami-0cf2b4e024cdb6960',
                'ubuntu_22' => 'ami-09c3a3c2cf6003f6c',
                'ubuntu_20' => 'ami-091c4300a778841cc',
            ],
            'ap-east-1' => [
                'ubuntu_24' => 'ami-026789b06a607b9a5',
                'ubuntu_22' => 'ami-0361acb22fef7522b',
                'ubuntu_20' => 'ami-0c0665dcea29a292d',
            ],
            'eu-central-2' => [
                'ubuntu_24' => 'ami-053ea2f9d1d6ac54c',
                'ubuntu_22' => 'ami-09407f9985de426af',
                'ubuntu_20' => 'ami-00c9866441e3616dd',
            ],
            'us-east-2' => [
                'ubuntu_24' => 'ami-09040d770ffe2224f',
                'ubuntu_22' => 'ami-0b986fc833876b42e',
                'ubuntu_20' => 'ami-010e55fe08af05fa7',
            ],
            'ap-northeast-3' => [
                'ubuntu_24' => 'ami-0b9bc7dcdbcff394e',
                'ubuntu_22' => 'ami-063600dcf13c07ebc',
                'ubuntu_20' => 'ami-0b7108d627f57c7c8',
            ],
            'sa-east-1' => [
                'ubuntu_24' => 'ami-04716897be83e3f04',
                'ubuntu_22' => 'ami-0e6dfcf4e0e4dfc52',
                'ubuntu_20' => 'ami-050e1159c5a10dd81',
            ],
            'ap-southeast-4' => [
                'ubuntu_24' => 'ami-0396cf525fd0aa5c1',
                'ubuntu_22' => 'ami-097638dc9b6250206',
                'ubuntu_20' => 'ami-02d0fccf5cdcdd8c5',
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
            'ubuntu_24' => 'linode/ubuntu24.04',
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
            'ubuntu_22' => '159651797',
            'ubuntu_24' => '160232537',
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
            'ubuntu_24' => '2284',
        ],
    ],
    'hetzner' => [
        'plans' => [

            /* Shared vCPUs x86 */
            [
                'title' => 'CX11 - 1 Cores (Intel) - 2 Memory - 20 Disk (eu only)',
                'value' => 'cx11',
            ],
            [
                'title' => 'CX22 - 2 Cores (Intel) - 4 Memory - 40 Disk (eu only)',
                'value' => 'cx22',
            ],
            [
                'title' => 'CPX11 - 2 Cores (AMD) - 2 Memory - 40 Disk (eu only)',
                'value' => 'cpx11',
            ],
            [
                'title' => 'CX21 - 2 Cores (Intel) - 4 Memory - 40 Disk (eu only)',
                'value' => 'cx21',
            ],
            [
                'title' => 'CX32 - 4 Cores (Intel) - 8 Memory - 80 Disk (eu only)',
                'value' => 'cx32',
            ],
            [
                'title' => 'CPX21 - 3 Cores (AMD) - 4 Memory - 80 Disk',
                'value' => 'cpx21',
            ],
            [
                'title' => 'CX31 - 2 Cores (Intel) - 8 Memory - 80 Disk (eu only)',
                'value' => 'cx31',
            ],
            [
                'title' => 'CPX31 - 4 Cores (AMD) - 8 Memory - 160 Disk',
                'value' => 'cpx31',
            ],
            [
                'title' => 'CX42 - 8 Cores (Intel) - 16 Memory - 160 Disk (eu only)',
                'value' => 'cx42',
            ],
            [
                'title' => 'CX41 - 4 Cores (Intel) - 16 Memory - 160 Disk (eu only)',
                'value' => 'cx41',
            ],
            [
                'title' => 'CPX41 - 8 Cores (AMD) - 16 Memory - 240 Disk',
                'value' => 'cpx41',
            ],
            [
                'title' => 'CX52 - 16 Cores (Intel) - 32 Memory - 320 Disk (eu only)',
                'value' => 'cx52',
            ],
            [
                'title' => 'CX51 - 8 Cores (Intel) - 32 Memory - 240 Disk (eu only)',
                'value' => 'cx51',
            ],
            [
                'title' => 'CPX51 - 16 Cores (AMD) - 32 Memory - 360 Disk',
                'value' => 'cpx51',
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

            /* Shared vCPUs Arm64 */
            [
                'title' => 'CAX11 - 2 Cores (ARM64) - 4 Memory - 40 Disk',
                'value' => 'cax11',
            ],
            [
                'title' => 'CAX21 - 4 Cores (ARM64) - 8 Memory - 80 Disk',
                'value' => 'cax21',
            ],
            [
                'title' => 'CAX31 - 8 Cores (ARM64) - 16 Memory - 160 Disk',
                'value' => 'cax31',
            ],
            [
                'title' => 'CAX41 - 16 Cores (ARM64) - 32 Memory - 320 Disk',
                'value' => 'cax41',
            ],

            /* Dedicated vCPUs */
            [
                'title' => 'CCX13 Dedicated CPU - 2 Cores (AMD) - 8 Memory - 80 Disk',
                'value' => 'ccx13',
            ],
            [
                'title' => 'CCX23 Dedicated CPU - 4 Cores (AMD) - 16 Memory - 160 Disk',
                'value' => 'ccx23',
            ],
            [
                'title' => 'CCX33 Dedicated CPU - 8 Cores (AMD) - 32 Memory - 240 Disk',
                'value' => 'ccx33',
            ],
            [
                'title' => 'CCX43 Dedicated CPU - 16 Cores (AMD) - 64 Memory - 360 Disk',
                'value' => 'ccx43',
            ],
            [
                'title' => 'CCX53 Dedicated CPU - 32 Cores (AMD) - 128 Memory - 600 Disk',
                'value' => 'ccx53',
            ],
            [
                'title' => 'CCX63 Dedicated CPU - 48 Cores (AMD) - 192 Memory - 960 Disk',
                'value' => 'ccx63',
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
            'ubuntu_20' => 'ubuntu-20.04',
            'ubuntu_22' => 'ubuntu-22.04',
            'ubuntu_24' => 'ubuntu-24.04',
        ],
    ],
];
