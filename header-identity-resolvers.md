---
section: Identity Resolvers
menu: Header
title: Header Identity Resolver
position: 2
slug: header-identity-resolvers
description:
  If you're building an API, or updating an existing API to be multitenanted, you could also use a HTTP header to provide the tenants identity
---

## Introduction

The header identity resolver is a driver for Sprouts [identity resolver](identity-resolvers) functionality
that uses a HTTP header to identify a tenant.

> [!WARNING]
> This identity resolver does not use the URL, so you cannot generate a link for a specific tenant.

## Configuring

When configuring your resolver in the [multitenancy config](configuration#identity-resolvers) you only need
to do one thing to use this identity resolver, set the driver to `header`.
An example config entry for this identity resolver looks like so.

```php
'resolvers' => [
    'header' => [
        'driver' => 'header',
    ],
]
```

You can also optionally provide the name of the header using the `header` config option.
This option allows you to provide placeholders like the other identity resolvers and their route parameters,
and will default to `{Tenancy}-Identifier`.

```php
'resolvers' => [
    'header' => [
        'driver' => 'header',
        'header' => '{Tenancy}-Identifier'
    ],
]
```

> [!NOTE]
> When providing a header name, you can use the placeholders `{tenancy}` and `{resolver}`.
> `{tenancy}` will be replaced by the registered name of the current tenancy,
> and `{resolver}` will be replaced by the registered name of the identity resolver.
> The default value is `{tenancy}_{resolver}`.
> You can also use `{Tenancy}`
> and `{Resolver}` which will run the value through `ucfirst()` to uppercase the first letter.

## Using

To make use of this identity resolver, once configured, either set
the [default resolver](configuration#multitenancy-defaults) to its name.

```php
'defaults' => [
    //...
    'resolver' => 'header',
]
```

Or provide its name as the second argument when registering tenanted routes.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'header');
```

The identity resolver itself is the `Sprout\Http\Resolvers\HeaderIdentityResolver` class
and has a few additional methods that may be of use.

```php
public function getHeaderName(): string

public function getRequestHeaderName(Tenancy $tenancy): string
```

The `getHeaderName()` method will return the config value `header`, 
and the `getRequestHeaderName()` will return the header for the current tenant of the provided tenancy.
