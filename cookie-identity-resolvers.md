---
section: Identity Resolvers
menu: Cookie
title: Cookie Identity Resolver
position: 3
slug: cookie-identity-resolvers
description:
  Sometimes you'll want to identify a tenant without having the identifier in the URL, and in a situation where you can't expect a HTTP header to be provided. For those situations, there's the cookie identity resolver
---

## Introduction

The cookie identity resolver is a driver for Sprouts [identity resolver](identity-resolvers) functionality
that uses a cookie to identify a tenant.

## Configuring

When configuring your resolver in the [multitenancy config](configuration#identity-resolvers) you only need
to do one thing to use this identity resolver, set the driver to `cookie`.
An example config entry for this identity resolver looks like so.

```php
'resolvers' => [
    'cookie' => [
        'driver' => 'cookie',
    ],
]
```

You can also optionally provide the name of the cookie using the `cookie` config option.
This option allows you to provide placeholders like the other identity resolvers and their route parameters,
and will default to `{Tenancy}-Identifier`.

```php
'resolvers' => [
    'cookie' => [
        'driver' => 'cookie',
        'cookie' => '{Tenancy}-Identifier'
    ],
]
```

> [!NOTE]
> When providing a cookie name, you can use the placeholders `{tenancy}` and `{resolver}`.
> `{tenancy}` will be replaced by the registered name of the current tenancy,
> and `{resolver}` will be replaced by the registered name of the identity resolver.
> The default value is `{tenancy}_{resolver}`.
> You can also use `{Tenancy}`
> and `{Resolver}` which will run the value through `ucfirst()` to uppercase the first letter.

You can further customise this identity resolver using the `options` config option,
which should contain a key value array of cookie options,
which will override the values retrieved from the `session.php` config file.
The following options are supported.

| Option      | Config              | Descriptions                                                       |
|-------------|---------------------|--------------------------------------------------------------------|
| `minutes`   |                     | The number of minutes until the cookie expires                     |
| `path`      | `session.path`      | The path the cookie should be bound to                             |
| `domain`    | `session.domain`    | The domain the cookie should be bound to                           |
| `secure`    | `session.secure`    | Whether the cookie should be secure only                           |
| `http_only` | `session.http_only` | Whether the cookie should be HTTP only, and hidden from Javascript |
| `same_site` | `session.same_site` | The cookie same site restrictions                                  |

## Using

To make use of this identity resolver, once configured, either set
the [default resolver](configuration#multitenancy-defaults) to its name.

```php
'defaults' => [
    //...
    'resolver' => 'cookie',
]
```

Or provide its name as the second argument when registering tenanted routes.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'cookie');
```

The identity resolver itself is the `Sprout\Http\Resolvers\CookieIdentityResolver` class
and has a few additional methods that may be of use.

```php
public function getCookieName(): string

public function getRequestCookieName(Tenancy $tenancy): string
```

The `getCookieName()` method will return the config value `header`,
and the `getRequestCookieName()` will return the cookie name for the current tenant of the provided tenancy.

> [!WARNING]
> This identity resolver does not use the URL, so you cannot generate a link for a specific tenant.
