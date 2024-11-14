---
section: Getting Started
title: Installation
position: 0
slug: index
description:
  A great place to start with Sprout is its installation. In fact, installation is the number one most recommended place to start when using a package.
---

[[TOC]]

## Requirements

Sprout has three requirements.

- PHP — 8.2 (`^8.2`)
- Laravel — 11.31 (`^11.31`)
- [Flysystem Path Prefixing][1] — 3.0 (`^3.0`)

> [!NOTE]
> The flysystem path prefixing package is only there for tenant-aware storage disks that prefix the path.
> The decision was made to include it by default to avoid additional installation steps.

## Install with Composer

Sprout is available on [packagist][2] and can be installed through Composer.

```shell
composer require sprout/sprout
```

> [!WARNING]
> You may have to adjust your `composer.json` if you attempt to install Sprout before its release.

## Publish Config

Once you have Sprout installed, you'll want to publish the config.

```shell
php artisan vendor:publish --provider="Sprout\\SproutServiceProvider"
```

## Next Steps

Now that you have Sprout installed, and the config files published, you can start configuring and implementing Sprout.
Before you move on though, you'll want to figure out your answers to the following questions:

- Which of Laravel's default services/features do I want to be tenant-aware?
- How do I want tenants to be identified? Using a subdomain? HTTP header? Session? Etc…
- What are my tenants? Are they Eloquent models or something else?

Your answers to these questions will help point you in the right direction for not only using Sprout,
but building your application.

The following are the recommended next steps that will work in most cases.
It's possible that your particular use-case and requirements will require additional steps,
but will most likely include these.

### Configure Sprouts Core

Sprout comes with two config files,
and the best one to start with is the one that [configures Sprout itself][3].
There are three options in here, though there's only really one that you should care about at this point.

#### Enable Service Overrides

Within the `sprout.php` config file, right at the bottom, is an option called `services`.
This is an array that contains the [service override][4] classes that should be enabled.
Sprout ships with the following service overrides:

- [Storage][5]
- [Jobs][6]
- [Cache][7]
- [Auth][8]
- [Cookies][9]
- [Sessions][10]

By default, all are enabled and in an appropriate order.
Since you've already asked yourself which parts of Laravel need to be made tenant-aware,
you'll know which to comment out/delete, if any.

> [!TIP]
> If there's an additional part of Laravel that isn't covered here,
> you can look at [creating a custom one][11].

### Configure your Multitenancy

The second config file that comes with Sprout is the [multitenancy config][12],
which lets you configure your implementation.

#### Configure your Identity Resolver

Within the `multitenancy.php` config file, there is an option `resolver`,
which, by default, contains a resolver for all the default drivers.
These resolvers are used to resolve a tenant's identifier from a route or request,
with each driver's name reflecting where in the request it expects to find it.

The default drivers are as follows:

> [!WARNING]
> Some identity resolvers will have limitations and restrictions regarding other features within Sprout or
> Laravel itself.
> Please read the documentation of your choice in full.

- [Subdomain][13]
- [Path][14]
- [Header][15]
- [Cookie][16]
- [Session][17]

> [!TIP]
> For simplicity, it is recommended that you remove any that you don't currently need.

#### Configure your Tenant Provider

In the same `multitenancy.php` config file, there's an option called `provider`,
which contains the configuration for each default tenant provider driver that Sprout comes with.
Tenant providers are used to retrieve instances of your tenant,
whether it's an Eloquent model, simply entity, or something else.

> [!TIP]
> The tenant provider functionality is mirrored on
> Laravel's [auth user provider][18] functionality.

The default drivers are as follows:

- [Eloquent][19]
- [Database][20]

For most people, their tenant will be an [Eloquent model][21].

> [!TIP]
> Should you wish to use something else as your tenant, you can look into [creating a custom tenant provider][22].

#### Configure your Tenancy

Once you have your resolver and provider configured,
you can finally configure your tenancy within the `tenancies` option inside the `multitenancy.php` config file.
Tenancies are the different types of tenants your application has, and for most people, there will be only one.

> [!TIP]
> The tenancies' functionality is mirrored on
> Laravel's [auth guard][23] functionality.

For your tenancy,
you'll want to set the provider you're using, which will be the one from the previous step,
as well as the [tenancy options][24].
The tenancy options configure some base functionality and behaviour of a tenancy.
You can read more about those in the configuration part of the documentation,
but for most people, the default ones you see should suffice.

#### Set the defaults

Finally, you'll want to go to the `defaults` option within the `multitenancy.php` file,
and update each with the name of the resolver, provider, and tenancy you just configured.
This step is entirely optional,
but, like with Laravel's auth functionality, this allows you to skip the manual providing of configuration names.

### Setting up Routes

Now that you're all configured, and you have your tenant,
whether that's an Eloquent model or something else, you're almost ready to start building.
Whatever you're building, you're most likely going to need some routes that require a tenant.

Fortunately, Sprout adds a helper method to the `Route` facade that makes creating tenanted routes simple.

```php
Route::tenanted(function () {
    // Define tenant routes here
});
```

This method does also allow you to specify the resolver and the tenancy in that order.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'subdomain', 'tenancy');
```

> [!WARNING]
> You can provide no additional options, just the resolver, or the resolver and the tenancy.
> It is not possible to provide just the tenancy.

The particular identity resolver that you are using may have additional limitations,
restrictions or requirements, so please read its documentation in full.

An example of this is
that the subdomain driver will require the non-tenanted routes
to be wrapped in a route group that explicitly sets the domain name.
This is to avoid non-tenanted routes being made available within tenancies.

### Start Building

Now you're all configured, and you've got a place to define your routes, so you can start building your application!

You don't need to write your application in a particular way to make Sprout work,
outside anything required by the resolver or provider, though those are mostly architectural limitations.
There are only two additional things to be aware of beyond this.

1. If you're using Eloquent for your tenant, your [tenant-owned models][25] will require the usage of a trait to hook into the automation.
2. The [storage][26] and [cache][27] service overrides will require you to create a tenant disk and store, respectively. The specifics of this can be found in their documentation.

Happy building!

[1]:	https://packagist.org/packages/league/flysystem-path-prefixing
[2]:	https://packagist.org/packages/sprout/sprout
[3]:	1.x/configuration#sprout-config
[4]:	1.x/service-overrides
[5]:	1.x/storage
[6]:	1.x/jobs
[7]:	1.x/cache
[8]:	1.x/auth
[9]:	1.x/cookies
[10]:	1.x/sessions
[11]:	1.x/custom-service-override
[12]:	1.x/configuration#multitenancy-config
[13]:	1.x/subdomain
[14]:	1.x/path
[15]:	1.x/header
[16]:	1.x/cookie
[17]:	1.x/session
[18]:	https://laravel.com/docs/11.x/authentication#adding-custom-user-providers
[19]:	1.x/eloquent
[20]:	1.x/database
[21]:	1.x/tenant-models
[22]:	1.x/custom-tenant-provider
[23]:	https://laravel.com/docs/11.x/authentication#adding-custom-guards
[24]:	1.x/configuration#tenancy-options
[25]:	1.x/tenant-child-models
[26]:	1.x/storage
[27]:	1.x/cache
