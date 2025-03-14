---
description: Service overrides all about making services tenant-aware, whether they're part of Laravel's core, or a third-party package.
---

> [!CALLOUT]
> The documentation is still in progress, and this page is not yet complete.
> Please check back again in the future.

## Introduction

Your application is made up of many "services", whether they're things like
[file storage](https://laravel.com/docs/12.x/filesystem),
[sessions](https://laravel.com/docs/12.x/session),
[authentication](https://laravel.com/docs/12.x/authentication), or one of the many others provided by Laravel.
Sometimes these services aren't part of the core Laravel installation, things like Fortify, Livewire, Inertia or
Filament.
Whatever they are, they aren't going to understand your application out of the box, and they're definitely not going
to support your multitenancy functionality.

This is where the service override comes in.
Service overrides are similar to service providers, except they're specific to the lifecycle of Sprout, as well as
that of a tenant.
They are called when the current tenant changes, allowing them to
set up for the new tenant, as well as clean up
after the previous.
They can also optionally be bootable, which means they have actions to perform once Laravel
has booted.

## Configuring Overrides

Service overrides are configured in the [`sprout.overrides` config](configuration#service-overrides) again the
"service" they override, with their class as the `driver`, and any extra configuration options required or allowed by
the driver itself.
The order that the overrides appear in the `sprout.overrides` config is the order that they'll be registered in, and
the order they'll be called in.

> [!WARNING]
> There are no specific rules surrounding the name that a service override is registered under, but it is recommended
> that you avoid changing them.

### Stacking Overrides

There are times when you'll need more than one service override for a service, and rather than having to register each
under a different name, Sprout supports stacking.
If you need to stack, you can set the driver to
[`\Sprout\Overrides\StackedOverride`](https://github.com/sprout-laravel/sprout/blob/1.x/src/Overrides/StackedOverride.php),
then provide the drivers you want to stack under the `overrides` config key.
If either driver requires config options, you can provide them normally.

```php
'filesystem' => [
    'driver'    => \Sprout\Overrides\StackedOverride::class,
    'overrides' => [
        \Sprout\Overrides\FilesystemManagerOverride::class,
        \Sprout\Overrides\FilesystemOverride::class,
    ],
],
```

There are two menu uses for stacking.

- The override for a particular service has been broken into smaller separate parts, and can be used
  independently of the others.
- You have multiple overrides from different sources, and want to use all of them.

### Per Tenancy

Once a service override is registered with Sprout, it is available for use.
By default, no service override is used unless the tenancy is configured to use it.
You can do this using [tenancy options](tenants#tenancy-options), by either enabling
[all overrides](tenants#all-overrides) for the tenancy, which is what the default config does.

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::allOverrides(),
    ],
],
```

Or by [listing only the ones you want](tenants#overrides).

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::overrides([
            'job', 'filesystem', 'cache'
        ]),
    ],
],
```

> [!NOTE]
> While you can enable or disable service overrides on a per tenancy basic, any that are bootable will be booted
> regardless, as that happens before the tenancy is identified.

### Setting and Cleaning Up

Service overrides are built around the concept of setting the override up for the tenant when it becomes the current
one, and cleaning up once it no longer is.
While this is a core part of this feature, both the setting and cleaning up are controlled by
[tenancy bootstrappers](configuration#tenancy-bootstrappers).

The [`\Sprout\Listeners\CleanupServiceOverrides`](https://github.com/sprout-laravel/sprout/blob/1.x/src/Listeners/CleanupServiceOverrides.php)
bootstrapper is responsible for making the service overrides clean-up after themselves,
and the [`\Sprout\Listeners\SetupServiceOverrides`](https://github.com/sprout-laravel/sprout/blob/1.x/src/Listeners/SetupServiceOverrides.php)
bootstrapper is responsible for the setup.

> [!WARNING]
> Both the order that these bootstrappers appear in the config, and their presence in it is important for the
> service override functionality.
> Removing either, or changing their order relative to each other, will have unknown side effects and may cause your
> application to not function properly.

## Available Service Overrides

Sprout ships with several overrides for core parts of Laravel, all of which are enabled by default.

### Filesystem

The filesystem override comes in two parts.
The first replace Laravel's filesystem manager with Sprouts, which only exists to simplify the second, which adds a
`sprout` driver that allows you to create
[tenant scoped filesystem disks](https://laravel.com/docs/12.x/filesystem#scoped-and-read-only-filesystems).

```php
'filesystem' => [
    'driver'    => \Sprout\Overrides\StackedOverride::class,
    'overrides' => [
        \Sprout\Overrides\FilesystemManagerOverride::class,
        \Sprout\Overrides\FilesystemOverride::class,
    ],
],
```

> [!NOTE]
> This service override requires that the tenant is [configured for resources](tenants#tenants-with-resources), and will
> throw an exception if it isn't.

#### Filesystem Manager

The filesystem manager service override does only a single thing, and that is adding the name of the disk to the
disk config as `name`, when calling a custom driver.
This is primarily of use to the other override, as it keeps track of the tenant disks that were accessed, and uses that
to perform the clean-up.

#### Filesystem

This service override is the one responsible for adding the `sprout` driver to the filesystem service.
The new driver allows you to create a filesystem disk scoped to the current tenant, either based on an existing disk,
or using new settings.

To use this override, create a filesystem disk in `config/filesystems.php` under `disks`, whose driver is `sprout`.
Then under the `disk` option, add either the name of an existing disk.

```php
'tenant-disk' => [
    'driver' => 'sprout',
    'disk'   => 'local',
],
```

> [!NOTE]
> This override uses the config from the other disk, and then scopes it, so you can use both the sprout one, and the
> base one simultaneously.
> Just be aware that the base one will have access to all data for all tenants.

Or the config for a new disk.

```php
'tenant-disk' => [
    'driver' => 'sprout',
    'disk'   => [
        'driver' => 'local',
        'root'   => storage_path('tenants'),
        'throw'  => false,
    ],
],
```

When the disk is created, its root will be set to `{tenancy}/{tenant}`, where `tenancy` is the name of the tenancy
from the `multitenancy.tenancies` config, and `tenant` is replaced with its
[resource key](tenants#tenants-with-resources).
If you want to change this, you can provide the `path` config option for the disk, which uses the same
placeholders as the [identity resolver parameter names](tenant-resolution#parameter-naming), except that `tenant`
uses the resource key, and not identifier.

```php
'tenant-disk' => [
    'driver' => 'sprout',
    'disk'   => 'local',
    'path'   => '/{tenancy}/files/{tenant}'
],
```

> [!WARNING]
> Sprout will not take steps to ensure that the disk path exists.
> This is something that you will need to do as part of your applications lifecycle whenever a new tenant is created.

### Job

The job service override is arguably the simplest of the defaults.
All it does is register an event listener for a [job event](https://laravel.com/docs/12.x/queues#job-events) that
makes sure that whenever a job is run, it has the same tenancies and tenants available as it did when it was first
queued.

```php
'job' => [
    'driver' => \Sprout\Overrides\JobOverride::class,
],
```

> [!NOTE]
> This is registered as `job` because it _technically_ does do anything with the queue, and naming it something like 
> that may get in the way or cause confusion.

### Cache

### Auth

### Cookie

### Session
