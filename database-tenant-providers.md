---
section: Tenant Providers
menu: Database
title: Database Tenant Provider
position: 1
slug: database-tenant-providers
description:
  Sometimes you don't want to use Eloquent, or you want to access the database without the overhead of Eloquent models. In that case, you can use the database tenant provider.
---

## Introduction

The database tenant provider is a driver for Sprouts [tenant provider](tenant-providers) functionality
that uses the database and a POPO (plain old PHP object) as your [tenant](tenants).

## Using

When configuring your provider in the [multitenancy config](configuration#tenant-providers) you only need
to do two things to use this tenant provider.
Set the driver to `database` and provide a table name using the `table` config option.
An example config entry for this tenant provider looks like so:

```php
'providers' => [
    'tenants' => [
        'driver' => 'database',
        'table'  => 'tenants'
    ],
]
```

This tenant provider was created, primarily,
to act as a secondary provider for cases where Eloquent would add unnecessary overhead.
The idea was that the [eloquent tenant provider](eloquent-tenant-providers) would still be used in most cases,
but this would be used in short-lived background operations.
So, if you're also using it this way,
you can provide an Eloquent model class instead of a table, and the models table name and connection name will be used.

```php
'providers' => [
    'tenants' => [
        'driver' => 'database',
        'table'  => MyTenantModel::class
    ],
]
```

By default, this tenant provider will use the default database connection,
but if you want it to use another, you can provide it with the `connection` option.

```php
'providers' => [
    'tenants' => [
        'driver'     => 'database',
        'table'      => 'tenants',
        'connection' => 'custom-connection'
    ],
]
```

This tenant provider makes use of the `Sprout\Support\GenericTenant` class as its implementation of the [
`Tenant` contract](tenants),
but if you want to provide your own, you can provide a class name using the `entity` option.

```php
'providers' => [
    'tenants' => [
        'driver' => 'database',
        'table'  => 'tenants',
        'entity' => MyTenant::class,
    ],
]
```

> [!WARNING]
> The entity you provide should have a constructor that accepts an array of attributes, stored as key-value pairs.

When the database tenant provider is created, the entity class will be validated and must meet the following criteria.

- It **MUST** exist at runtime.
- It **MUST** implement the `Sprout\Contracts\Tenant` interface, either directly or indirectly.

If the provided value does not meet these criteria,
a [misconfiguration exception](exceptions#invalid-config) will be thrown.
