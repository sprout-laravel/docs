---
section: Core Concepts
title: Service Overrides
position: 3
slug: service-overrides
description:
  Your multitenanted application will make use of many services, whether provided by Laravel itself, or a third-party package. These services won't know about your tenants, or even multitenancy. Service overrides allow you to customise and override some parts of these services to make them tenant-aware.
---

## Introduction

Service overrides are classes within Sprout
that exist specifically to override or customise Laravel or third-party services to make them tenant-aware.
The classes themselves are similar to Laravel service providers, though they're handled by code specific to the purpose.

## How they work

Service overrides work based on lifecycle hooks within the booting of the Laravel framework and Sprout itself.
The hooks they have access to will depend on the type of service override they are.

### Base Service Overrides

All service overrides will implement the `Sprout\Contracts\ServiceOverride` interface, whether directly or indirectly,
and will have access to the `setup` and `cleanup` lifecycle hooks.

> [!NOTE]
> While all service overrides have access to these hooks,
> and must define the corresponding methods, not all of them actually do anything with them.

#### The `setup` hook

The [`setup` hook][1] refers to the tenancy lifecycle hook
where the current tenant changes to a new tenant.
This includes the changing of the tenant during a request,
as well, the initial setting of a tenant, where the previous tenant, could be considered `null`.

This hook is intended to be used to set up anything necessary for the multitenanted functionality,
whether it's generic or specific to the current tenant.

```php
public function setup(Tenancy $tenancy, Tenant $tenant): void;
```

#### The `cleanup` hook

The [`cleanup` hook][2] refers to the tenancy lifecycle hook where the current tenant changes,
but unlike with the `setup` hook, it's only used if there was a previous tenant.
In situations where this hook is used, it comes before the `setup` and will always be given the previous tenant.

The hook is intended to be used to clean up anything that may be left over from the previous tenant.
This is particularly useful in situations where driver config is dynamically generated based on the tenant.
If you don't manually clean up the old driver, the new tenant will have access to services specific to the previous.

```php
public function cleanup(Tenancy $tenancy, Tenant $tenant): void;
```

### Deferrable Service Overrides

From a performance perspective, you probably don't want to load database connections,
filesystem disks, cache stores, auth guards or any other number of things as soon as the tenant changes.
To address this,
a service override can be marked as deferrable
by implementing the `Sprout\Contracts\DeferrableServiceOverride` interface.

Deferrable service overrides provide the name of a service,
and won't be set up until after that service has been resolved using Laravel's container.
These service overrides will also not be instantiated (`new MyServiceOverride`) until the deferred service is loaded,
so you can use constructor dependency injection.

```php
public static function service(): string;
```

> [!WARNING]
> The value returned by this method **must** match a binding within the container,
> as it will be used to hook into the Laravel containers after resolving event.

### Bootable Service Overrides

Some service overrides will have actions they need to perform
before the application even thinks about setting the current tenant.
These service overrides can implement the `Sprout\Contracts\BootableServiceProvider` interface,
allowing them to be "booted" once Laravel has been booted.

Bootable service overrides are booted
using
Laravel's [booted lifecycle callback][3],
so any non-deferred service providers will have already been booted.

```php
public function boot(Application $app, Sprout $sprout): void;
```

> [!WARNING]
> Bootable service providers that are also deferrable will have their booting deferred as well.

## Provided Implementations

Sprout includes a handful of service overrides for working with Laravel's core services.

### File Storage

The file storage override registers a storage driver called `sprout`,
which can be used to wrap/duplicate another filesystem disk, but make it tenant-aware.
You can read more about this override [here][4].

### Queued Jobs

The queued job override is one of the simplest.
When running a job, the current tenant will be set based on the value within the context.
You can read more about this override [here][5].

### Cache Stores

The cache stores override works in the same way as the file storage override.
It adds a driver called "sprout" that lets you wrap/duplicate another cache store, but makes it tenant-aware.
You can read more about this override [here][6].

### Authentication

As long as your user models are defined as [tenant child models][7],
they'll automatically be tenant-aware.
However, the password reset handler doesn't use Eloquent models, so needs to be overridden and made tenant-aware.
You can read more about this override [here][8].

### Cookies

The cookie override is, like the queued job override, implicit and requires no additional configuration.
It does, however, only really work if you're identifying tenants using part of the URL.
While it won't error,
using this override with the [cookie identity resolver][9] should be avoided.
You can read more about this override [here][10].

### Sessions

The session override is another implicit service override
that works in a very similar way to the cookie override, at least in part.
Rather than modify the defaults used to create a cookie,
it modifies the session config in memory.
It also wraps the default session drivers, making them tenant-aware.
You can read more about this override [here][11].

## Need something else?

While the provided overrides should suffice for the core services offered by Laravel,
that's a good chance that you'll be using something else as well.
For these situations, you can create yourself a [custom service override][12].
There are plans to support many third-party packages, including Laravel's first-party offerings.
Have a look at [the discussion][13] about it.

[1]:	lifecycle#tenancy-setup
[2]:	lifecycle#tenancy-cleanup
[3]:	https://github.com/laravel/framework/blob/11.x/src/Illuminate/Foundation/Application.php#L1144
[4]:	storage-service-override
[5]:	jobs-service-override
[6]:	cache-service-override
[7]:	tenant-child-models
[8]:	auth-service-override
[9]:	cookie-identity-resolvers
[10]:	cookie-service-override
[11]:	session-service-override
[12]:	custom-service-overrides
[13]:	https://github.com/orgs/sprout-laravel/discussions/72
