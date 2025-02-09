<?php

return [
    'entry' => 'installation',

    'menu' => [
        'Getting Started' => [
            'Installation'  => 'installation',
            'Configuration' => 'configuration',
        ],

        'Core Concepts' => [
            'Tenants'           => 'tenants',
            'Tenant Resolution' => 'tenant-resolution',
            'Service Overrides' => 'service-overrides',
            'Eloquent'          => 'eloquent',
        ],

        'Advanced' => [
            'Tenant Providers'   => 'tenant-providers',
            'Identity Resolvers' => 'identity-resolvers',
            'Tenancies'          => 'tenancies',
            'Exceptions'         => 'exceptions',
            'Context'            => 'context',
        ],

        'Addons' => [
            'Bud'      => 'bud',
            'Seedling' => 'seedling',
            'Terra'    => 'terra',
        ],
    ],
];
