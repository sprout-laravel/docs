---
description: While Sprout comes with sensible defaults meaning you'll rarely need to mess with the config, it's worth taking a look at what you can do. You never know when it'll come in handy.
---

## Introduction

Sprout comes with a handful of configuration files, all of which have sensible defaults that should work for most
applications.
However, it doesn't hurt to familiarise yourself with what's available to you.
Although every effort has been taken to limit the amount of configuration required, the decision to break it down
into smaller chunks was made, to allow for it to be more manageable.
All Sprout config follows the standard [Laravel approach](https://laravel.com/docs/11.x/configuration), so
can be interacted with in the same way.

### Multitenancy Configuration

The primary configuration for Sprout, and the one you're most likely to interact with, is the `multitenancy.php` file,
which is published to `config/multitenancy.php`.
This file allows you to customise the multitenanted aspects of your application, including:

- The tenancies it supports
- The different tenant providers
- The available identity resolvers

If you're familiar enough with Laravel's other configuration, you'll notice that this file is very similar to others,
particularly the `auth.php` file.
This is because the design and structure of Sprout is quite heavily based on Laravel's auth functionality.

### Sprout Configuration

The rest of the configuration provided by Sprout is published to the `config/sprout` directory.
One of the main reasons this subdirectory is used, is so that addons can publish their own configuration files to it,
making them available under the `sprout.` config prefix.
By default, this directory contains:

- Core Sprout configuration in `core.php`
- Service override configuration in `overrides.php`

## Configuring

Now that we've covered the different types of configuration, and where they sit on the filesystem,
let's look into what you can actually configure.

### Multitenancy Defaults

The first part of the [multitenancy configuration](#multitenancy-configuration) is called `multitenancy.defaults`, 
which functions identically to `auth.defaults`, except that it's for Sprout.

```php
'defaults' => [
    'tenancy'  => 'tenants',
    'provider' => 'tenants',
    'resolver' => 'subdomain',
],
```

There are lots of different ways to interact with [tenancies](tenants#tenancies), 
[tenant providers](tenants#tenant-providers),
and [identity resolvers](tenant-resolution)within Sprout, and this is where you can tell 
it, which to use by default, when none are specified.
Remember that if you change the name of one of these, you'll need to update the `defaults` section to reflect that.
Most of you will only be dealing with one tenancy, provider, and resolver, and here is where they'll be set.

### Tenancies

The next option within the [multitenancy configuration](#multitenancy-configuration) config, is `multitenancy.
tenancies`, which lets
you define the different tenancies that your application supports, again, similar to `auth.guards`.

```php
'tenancies' => [
    'tenants' => [
        'provider' => 'tenants',
        'options'  => [
            TenancyOptions::hydrateTenantRelation(),
            TenancyOptions::throwIfNotRelated(),
            TenancyOptions::allOverrides(),
        ],
    ],
],
```

Tenancies are unlike tenant providers and identity resolvers, as the default implementation doesn't require any options,
and there's no support beyond a default implementation.
That being said, there are three possible options that can be provided.

#### Tenancy Driver

The `driver` option of the `tenancies` config is purely optional.
Sprout has a default tenancy implementation, which is used when this option is missing, or set to `null`.
You can read more about [custom tenancies here](tenancies).
This option is missing by default.

#### Tenancy Provider

The `provider` option is also entirely optional, with Sprout using the provider defined in `multitenancy.defaults.
provider` when it's missing.
If you wish to use a different provider, or manually set configure it, this value must correspond with one from
the [tenant providers](#tenant-providers) section.
By default, this will be set to the `tenants` provider.

#### Tenancy Options

The `options` option is also optional, and when provided should be an `array`.
The values in this array come from the `Sprout\TenancOptions` class, which act as feature flags within a tenancy.
Values within here are do not _have to_ come from the `TenancyOptions` class, as addons and third-party packages
may add their own here.
By default, the `tenants` tenancy has three options:

- `hydrateTenantRelation` - This option enables to automatic hydration of the tenant relation on a [tenant child
  model](eloquent).
- `throwIfNotRelated` - This option will throw an exception if a tenant child model is not related to the current
  tenant.
- `allOverrides` - This option will allow all service overrides to be used within the tenancy.

You can read more about [tenancy options here](tenants#tenancy-options), including which are available, and how to access them.

### Tenant Providers

Next comes the `multitenancy.providers` section of the [multitenancy configuration](#multitenancy-configuration), 
which is where you can define the different tenant providers that your application supports, 
similar to `auth.providers`.

```php
'providers' => [
    'tenants' => [
        'driver' => 'eloquent',
        'model'  => \Sprout\Database\Eloquent\Tenant::class,
    ],
],
```

Tenant providers are responsible for retrieving instances of your tenant for a given request, and this is where you
can define the different ones available to you.
Providers only require that the `driver` option is provided, with all other options being dependent on the driver
used.

By default, the `eloquent` driver is used, which requires the `model` option.
The value provided for `model` is an abstract model within Sprout, but if you've followed the
[installation](/docs/1.x/installation#creating-your-tenant) guide, you'll have already updated this.
You can read more about the available [tenant provider drivers here](tenants), and more about tenant providers as a
concept [here](tenant-providers).

### Identity Resolvers

The final part of the [multitenancy configuration](#multitenancy-configuration) is the `multitenancy.resolvers` option, 
which is where you can define the different identity resolvers for your application.
This feature has no corresponding component of the default auth library, as this functionality is baked into the guards.

```php
'resolvers' => [
    'subdomain' => [
        'driver'  => 'subdomain',
        'domain'  => env('TENANTED_DOMAIN'),
        'pattern' => '.*',
    ],
    'header' => [
        'driver' => 'header',
        'header' => '{Tenancy}-Identifier',
    ],
    'path' => [
        'driver'  => 'path',
        'segment' => 1,
    ],
    'cookie' => [
        'driver' => 'cookie',
        'cookie' => '{Tenancy}-Identifier',
    ],
    'session' => [
        'driver'  => 'session',
        'session' => 'multitenancy.{tenancy}',
    ],
],
```

By default, Sprout comes with a configuration for every identity resolver that is supported out of the box.
Most of you won't ever need to change any of this, however, you are free to do so.
Just like with tenant providers, identity resolvers only require the `driver` option, with the rest of the options
depending on the driver that you use.
You can read more about available [identity resolvers here](tenant-resolution), and more about identity resolvers as 
a concept [here](identity-resolvers).

### Resolution Hooks

Now we're over to the `config/sprout/core.php` config file, which provides some of the core sprout configuration, as 
the name may suggest.
The first option in this file is the `sprout.core.hooks` option, which define the locations where Sprout will 
attempt to hook into a Laravel lifecycle to identity a tenant.

```php
'hooks' => [
    // \Sprout\Support\ResolutionHook::Booting,
    \Sprout\Support\ResolutionHook::Routing,
    \Sprout\Support\ResolutionHook::Middleware,
],
```

This option is a simple array of cases from the enum `Sprout\Support\ResolutionHook`. 
The presence of a hook within this will control whether certain things are booted or registered.
By default, only `Routing` and `Middleware` are enabled, as `Booting` doesn't currently do anything.
It's there for future compatibility, so it doesn't need to be enabled, although doing so won't break anything.
You can read more about resolution hooks [here](tenant-resolution).

### Tenancy Bootstrappers

The last part of the core config is the `sprout.core.bootstrappers` option, which contains a collection classes that 
should be used to bootstrap a tenancy, once a tenant becomes the active tenant.

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
    // Refresh anything that's tenant-aware
    \Sprout\Listeners\RefreshTenantAwareDependencies::class,
],
```

Tenancy bootstrappers are listeners that handle the `Sprout\Events\CurrentTenantChanged` event. 
Any classes that are listed here will be registered as listeners for that event.
This option only exists to give you control over the process, whether you want to remove one of these, or add your own. 
If you are creating your own tenancy bootstrapper (event listener), you only actually need to add it to this array, 
if you need it to be called before one of these.

### Service Overrides

The final part of the default configuration is the `config/sprout/overrides.php` file, which represents the
`sprout.overrides` config option.
Rather than having several smaller options within, this config file is the entire option itself, and lets you list out 
the service overrides that should be registered with Sprout, as well as any necessary configuration.

```php
return [
    'filesystem' => [
        'driver'    => \Sprout\Overrides\StackedOverride::class,
        'overrides' => [
            \Sprout\Overrides\FilesystemManagerOverride::class,
            \Sprout\Overrides\FilesystemOverride::class,
        ],
    ],
    'job' => [
        'driver' => \Sprout\Overrides\JobOverride::class,
    ],
    'cache' => [
        'driver' => \Sprout\Overrides\CacheOverride::class,
    ],
    'auth' => [
        'driver'    => \Sprout\Overrides\StackedOverride::class,
        'overrides' => [
            \Sprout\Overrides\AuthGuardOverride::class,
            \Sprout\Overrides\AuthPasswordOverride::class,
        ],
    ],
    'cookie' => [
        'driver' => \Sprout\Overrides\CookieOverride::class,
    ],
    'session' => [
        'driver'   => \Sprout\Overrides\SessionOverride::class,
        'database' => false,
    ],
];
```

The reason this is a dedicated file is that service override configuration can start to get quite long.
Service overrides are designed to make a "service" multitenanted.
In the case of the default installation, this simply means components of Laravel, but would also include things like
Livewire, Filament, Telescope, Nova, etc., etc.

It can get even worse; when like the auth and filesystem override, they're split into multiple parts.
To find out more about the available service overrides, as well as service overrides as a concept, check out the 
[documentation for them](service-overrides).
