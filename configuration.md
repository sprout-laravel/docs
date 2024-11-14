---
section: Getting Started
title: Configuration
position: 1
slug: configuration
description:
  Sprout has a handful of configuration options, and while they are all set to sensible defaults, it's worth understanding what each of them does.
---

[[TOC]]

## Introduction

Sprout comes with two config files, `sprout.php` and `multitenancy.php`.
You can publish these config files using Laravel's `vendor:publish` command, like so:

```shell
php artisan vendor:publish --provider="Sprout\\SproutServiceProvider"
```

The config is also tagged as `config` and `sprout-config`, if you prefer to publish that way.

## Multitenancy Config

The first of the two config files is `multitenancy.php` which is where you configure your multitenancy implementation.
If you've ever had to work with Laravel's `auth.php` config file, some of this may be familiar to you.

## Sprout Config

The second of the two config files is `sprout.php` which contains configuration specific to the general running of Sprout
itself.
This file has three separate config options.

### Enabled Hooks

The `hooks` option within the Sprout config controls
where in the lifecycle of the Laravel application, Sprout should attempt to identity a tenant.

```php
'hooks' => [
    // \Sprout\Support\ResolutionHook::Booting,
    \Sprout\Support\ResolutionHook::Routing,
    \Sprout\Support\ResolutionHook::Middleware,
],
```

The values are provided by the enum `Sprout\Support\ResolutionHook`, and the following are available:

#### `ResolutionHook::Routing`

This hook will tell Sprout
to attempt to identify tenants when receiving a `Illuminate\Routing\Events\RouteMatched` event.
This is the recommended location for tenant identification, and will suffice for most use cases.

#### `ResolutionHook::Middleware`

This hook will tell Sprout to attempt to identify tenants within the middleware phase of routing.
It is recommended
that you keep this hook enabled
as it allows for fallback identification should the routing approach fail for some reason.

#### `ResolutionHook::Booting`

This hook will tell Sprout to attempt to identify tenants during the booting of the framework,
more specifically, in the boot phase of a service provider.

> [!WARNING]
> This hook is only present for future compatibility, and is not currently supported.

### Bootstrappers

The `bootstrappers` option within the Sprout config is a priority ordered list of event listeners
that should run when a tenant becomes the current tenant.

```php
'bootstrappers' => [
    // Set the current tenant within the Laravel context
    \Sprout\Listeners\SetCurrentTenantContext::class,
    // Calls the setup method on the current identity resolver
    \Sprout\Listeners\PerformIdentityResolverSetup::class,
    // Performs any clean-up from the previous tenancy
    \Sprout\Listeners\CleanupServiceOverrides::class,
    // Sets up service overrides for the current tenancy
    \Sprout\Listeners\SetupServiceOverrides::class,
],
```

These listeners are all for the `Sprout\Events\CurrentTenantChanged` event,
and are registered during the boot phase of the Sprout service provider.
The list exists specifically so that you can control the default behaviour of bootstrapping a tenancy.

Since these are simply listeners to a Sprout event, you can create your own with the following command:

```shell
php artisan make:listener MyTenancyBootstrapper --event="\\Sprout\\Events\\CurrentTenantChanged"
```

> [!NOTE]
> If you wish to create your own bootstrapper,
> you only need to add it to this list if the order in which it is fired is important.

#### `SetCurrentTenantContext`

The `SetCurrentTenantContext` bootstrapper ensures
that the current tenant for all active tenancies has their key present
within [Laravel's context][1].
The context key is `sprout.tenants`,
which contains an array of `tenancy => key` mappings,
where `tenancy` is the name of the tenancy configured in `multitenancy.tenancies`, and `key` is the tenant's key.

#### `PerformIdentityResolverSetup`

Some [identity resolvers][2] have actions that should be performed
when a tenancy is bootstrapped off the back of them.
For example,
[parameter-based identity resolvers][3] will set default values for the route
parameters,
so you don't have to manually provide them when generating a route URL.

#### `CleanupServiceOverrides`

Since Sprout allows you to switch the current tenant during a request,
it's entirely possible that a single request bootstraps two tenants tenancies.
Because of this,
Sprouts [service overrides][4] can have clean-up actions to prevent tenant configuration,
services and set-ups from leaking.

> [!WARNING]
> This bootstrapper should always come before the `SetupServiceOverrides`,
> because some of Laravel's services will use old configured instances if they're still around.

#### `SetupServiceOverrides`

This particular bootstrapper is responsible
for allowing [service overrides][5] to perform their various setup actions.
Not all overrides will have setup actions,
but if they do, what they are will depend entirely on the service they're overriding.

### Services

The final option in the Sprout config is `services`,
which contains the [service overrides][6] that should be enabled for the application.

```php
'services' => [
    // This will override the storage by introducing a 'sprout' driver
    // that wraps any other storage drive in a tenant resource subdirectory.
    \Sprout\Overrides\StorageOverride::class,
    // This will hydrate tenants when running jobs, based on the current
    // context.
    \Sprout\Overrides\JobOverride::class,
    // This will override the cache by introducing a 'sprout' driver
    // that adds a prefix to cache stores for the current tenant.
    \Sprout\Overrides\CacheOverride::class,
    // This is a simple override that removes all currently resolved
    // guards to prevent user auth leaking.
    \Sprout\Overrides\AuthOverride::class,
    // This will override the cookie settings so that all created cookies
    // are specific to the tenant.
    \Sprout\Overrides\CookieOverride::class,
    // This will override the session by introducing a 'sprout' driver
    // that wraps any other session store.
    \Sprout\Overrides\SessionOverride::class,
],
```

> [!NOTE]
> The order that the service overrides appear within this list is the order that they will be added in.

By default, all the available service overrides that Sprout ships with are enabled.
You can find out more about the individual service overrides from within their documentation.

The following are available as part of Sprout:

- [Storage][7]
- [Jobs][8]
- [Cache][9]
- [Auth][10]
- [Cookies][11]
- [Sessions][12]

[1]:	https://laravel.com/docs/11.x/context
[2]:	1.x/identity-resolvers
[3]:	1.x/identity-resolvers#parameter-based
[4]:	1.x/service-overrides
[5]:	1.x/service-overrides
[6]:	1.x/service-overrides
[7]:	1.x/storage
[8]:	1.x/jobs
[9]:	1.x/cache
[10]:	1.x/auth
[11]:	1.x/cookies
[12]:	1.x/sessions
