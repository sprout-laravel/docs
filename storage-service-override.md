---
section: Service Overrides
menu: Storage
title: Storage Service Override
position: 0
slug: storage-service-override
description:
  The storage service override allows you to make Laravels filesystem disks tenant-aware, allowing each tenant to have their own storage location(s)
---

## Introduction

The storage service override is a driver for Sprouts [service override](service-overrides) functionality,
that makes Laravel's built-in [storage/filesystem service](https://laravel.com/docs/11.x/filesystem) tenant-aware.
The service override is [deferrable](service-overrides#deferrable-service-overrides) until the `filesystem`
(`\Illuminate\Filesystem\FilesystemManager`) service is resolved.
It's also [bootable](service-overrides#bootable-service-overrides).

## How it works

The storage service override works by registering a custom filesystem driver called `sprout`.
This driver uses
Laravel's [scoped filesystem](https://laravel.com/docs/11.x/filesystem#scoped-and-read-only-filesystems) functionality
to automatically prefix a pre-configured disks path using a [tenants' resource key](tenants#tenants-with-resources).

> [!TIP]
> This service override doesn't replace the original disk,
> it instead creates a "clone" with a prefixed path,
> allowing you to use both the tenanted and non-tenanted versions simultaneously, without worrying.

If the current request/process is one where the current tenant changes from one to another,
any previously created/loaded tenant-aware disk will be removed from the filesystem manager,
to avoid data-leaking between tenants.

## Using

To use the storage service override,
you must first make sure that it is registered in the [services section](configuration#services) of
the [sprout config](configuration#sprout-config).

```php
'services' => [
    \Sprout\Overrides\StorageOverride::class,
],
```

Once its registered, you need to create disk within Laravel's `filesystem.php` config file,
specifying `sprout` as the driver.

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root'   => storage_path('app'),
        'throw'  => false,
    ],
    
    'tenanted' => [
        'driver' => 'sprout',
    ],
]
```

By default, Sprout will clone whichever disk is set as the default,
but if you want to override this, you can provide the name of the disk using the `disk` config option.

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root'   => storage_path('app'),
        'throw'  => false,
    ],
    
    'tenanted' => [
        'driver' => 'sprout',
        'disk'   => 'local',
    ],
]
```

Alternatively, instead of the name of a filesystem disk,
you can provide a disk config which will be used in a similar fashion to
Laravel's [on-demand disks](https://laravel.com/docs/11.x/filesystem#on-demand-disks).

```php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root'   => storage_path('app'),
        'throw'  => false,
    ],
    
    'tenanted' => [
        'driver' => 'sprout',
        'disk'   => [
            'driver' => 'local',
            'root'   => storage_path('tenants'),
        ],
    ],
]
```

> [!WARNING]
> You cannot nest `sprout` driver disks, attempting to do so will cause an exception to be thrown.

Once you have your disk configured, you can use it as you would any other, if the following criteria are met.

- You are currently within [multitenanted context](multitenanted-context)
- There is a current tenancy
- The current tenancy has a current tenant

```php
Storage::disk('tenanted')->put('image.jpg', $content);

$contents = Storage::disk('tenanted')->get('image.jpg');

// Etc, etc
```

## Considerations

This implementation of a storage service override works based on the idea of separating tenant files by subdirectory.
If you require something more complex,
like each tenant having their own disk configuration for external services like AWS S3,
etc.,
then it may be worth considering [Sprout Bud](first-party-packages#bud) a first party package for just such an occasion.
