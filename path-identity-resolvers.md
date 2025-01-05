---
section: Identity Resolvers
menu: Path
title: Path Identity Resolver
position: 1
slug: path-identity-resolvers
description:
  Want to use a portion of the URLs path to identify the tenant? Well, the path identity resolver is here for that exact purpose
---

## Introduction

The path identity resolver is a driver
for Sprouts [identify resolver](identity-resolvers) functionality
that uses a portion of the URLs path to identify a tenant.

## Configuring

When configuring your resolver in the [multitenancy config](configuration#identity-resolvers) you only need
to do one thing to use this identity resolver, set the driver to `path`.
An example config entry for this identity resolver looks like so.

```php
'resolvers' => [
    'path' => [
        'driver' => 'path',
    ],
]
```

As the path identity resolver is a [route parameter-based identity resolver](identity-resolvers#route-parameters),
it accepts two other optional config values.
The first is `pattern`,
which allows you
to provide
a [regular expression constraint](https://laravel.com/docs/11.x/routing#parameters-regular-expression-constraints) for
the parameter,
and the second is `parameter` that lets you provide the parameter name.

```php
'resolvers' => [
    'path' => [
        'driver'    => 'path',
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

As all route parameter-based identity resolvers can also be used without a route,
the path identity resolver will need to know which segment of the path contains should contain the tenants'
identity, which can be provided using the `segment` config option.

```php
'resolvers' => [
    'path' => [
        'driver'  => 'path',
        'segment' => 1,
    ],
]
```

> [!NOTE]
> This value is 1-index, so should not go lower than 1. It defaults to `1`.

## Using

To make use of this identity resolver, once configured, either set
the [default resolver](configuration#multitenancy-defaults) to its name.

```php
'defaults' => [
    //...
    'resolver' => 'path',
]
```

Or provide its name as the second argument when registering tenanted routes.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'path');
```

The identity resolver itself is the `Sprout\Http\Resolvers\PathIdentityResolver` class
and has a few additional methods that may be of use.

```php
public function getSegment(): int

public function getRoutePrefix(Tenancy $tenancy): string

public function getTenantRoutePrefix(Tenancy $tenancy): string
```

The `getSegment()` method will return the config value `segment`.
The `getRoutePrefix()` method will return the path prefix used for the route definition (`/{parameter}/`),
and the `getTenantRoutePrefix()` will return the route prefix for the current tenant of the provided tenancy.

## Side Effects

As a [route parameter-based identity resolver](identity-resolvers#route-parameters),
a default value for the route parameter will be set during the setup phase of the identity resolver
(when a tenant becomes the current tenant).
This means
that if you need to generate a URL in the future from a route that has a parameter from this identity resolver,
you don't need to provide it.

It also sets the internal [Sprout setting `url.path`](internal-settings#url-path) to the current path,
also during the setup phase.
