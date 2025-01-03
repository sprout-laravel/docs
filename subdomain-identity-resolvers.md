---
section: Identity Resolvers
menu: Subdomain
title: Subdomain Identity Resolver
position: 0
slug: subdomain-identity-resolvers
description:
  Want to use the subdomain portion of the URL to identity a tenant? Well, the subdomain identity resolver is here for that exact purpose
---

## Introduction

The subdomain identity resolver is a driver
for Sprouts [identify resolver](identity-resolvers) functionality
that uses the subdomain portion of the URL to identify a tenant.

## Configuring

When configuring your resolver in the [multitenancy config](configuration#identity-resolvers) you only need
to do two things to use this identity resolver.
Set the driver to `subdomain` and provide a parent domain using the `domain` config option.
An example config entry for this identity resolver looks like so.

```php
'resolvers' => [
    'subdomain' => [
        'driver' => 'subdomain',
        'domain' => 'mydomain.com'
    ],
]
```

> [!TIP]
> The default config uses a `TENANTED_DOMAIN` environment variable,
> and it is recommended that you use an environment variable, whether this one or another,
> to provide the `domain` config value.

As the subdomain identity resolver is a [route parameter-based identity resolver](identity-resolvers#route-parameters),
it accepts two other optional config values.
The first is `pattern`,
which allows you
to provide
a [regular expression constraint](https://laravel.com/docs/11.x/routing#parameters-regular-expression-constraints) for
the parameter,
and the second is `parameter` that lets you provide the parameter name.

```php
'resolvers' => [
    'subdomain' => [
        'driver'    => 'subdomain',
        'domain'    => 'mydomain.com'
        'pattern'   => '.*',
        'parameter' => '{tenancy}_resolved_by_{resolver}'  
    ],
]
```

> [!NOTE]
> When providing a custom parameter name, you can use the placeholders `{tenancy}` and `{resolver}`.
> `{tenancy}` will be replaced by the registered name of the current tenancy,
> and `{resolver}` will be replaced by the registered name of the identity resolver.
> The default value is `{tenancy}_{resolver}`.

## Using

To make use of this identity resolver, once configured, either set
the [default resolver](configuration#multitenancy-defaults) to its name.

```php
'defaults' => [
    //...
    'resolver' => 'subdomain',
]
```

Or provide its name as the second argument when registering tenanted routes.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'subdomain');
```

The identity resolver itself is the `Sprout\Http\Resolvers\SubdomainIdentityResolver` class
and has a few additional methods that may be of use.

```php
public function getDomain(): string

public function getRouteDomain(Tenancy $tenancy): string

public function getTenantRouteDomain(Tenancy $tenancy): string
```

The `getDomain()` method will return the config value `domain`.
The `getRouteDomain()` method will return domain used for the route definition (`{parameter}.domain-setting`),
and the `getTenantRouteDomain()` will return the fully qualified domain for the current tenant of the provided tenancy.

## Side Effects

As a [route parameter-based identity resolver](identity-resolvers#route-parameters),
a default value for the route parameter will be set during the setup phase of the identity resolver
(when a tenant becomes the current tenant).
This means
that if you need to generate a URL in the future from a route that has a parameter from this identity resolver,
you don't need to provide it.

It also sets the internal [Sprout setting `url.domain`](internal-settings#url-domain) to the current domain,
also during the setup phase.
