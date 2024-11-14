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

## Sprout Config

The first of the two config files is `sprout.php` which contains configuration specific to the general running of Sprout
itself.
This file has three separate config options.

### Enabled Hooks

The `hooks` option within the Sprout config controls
where in the lifecycle of a Laravel application Sprout should attempt to identity a tenant.

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
that the current tenant for all active tenancies has their key present within [Laravel's context](https://laravel.com/docs/11.x/context).
The context key is `sprout.tenants`,
which contains an array of `tenancy => key` mappings,
where `tenancy` is the name of the tenancy configured in `multitenancy.tenancies`, and `key` is the tenants key.

#### `PerformIdentityResolverSetup`

Some [identity resolvers](1.x/identity-resolvers) have actions that should be performed
when a tenancy is bootstrapped off the back of them.
For example, [parameter-based identity resolvers](1.x/identity-resolvers#parameter-based) will 
