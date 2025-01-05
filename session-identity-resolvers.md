---
section: Identity Resolvers
menu: Session
title: Session Identity Resolver
position: 4
slug: session-identity-resolvers
description:
  Sometimes you'll want to identify a tenant without having the identifier in the URL, in a situation where you can't expect a HTTP header to be provided, and when you want something a little more dynamic than a cookie. For those situations, there's the session identity resolver
---

## Introduction

The session identity resolver is a driver for Sprouts [identity resolver](identity-resolvers) functionality
that uses a value stored in the session to identify a tenant.

> [!WARNING]
> This identity resolver does not use the URL, so you cannot generate a link for a specific tenant.

> [!WARNING]
> This identity resolver is not compatible with the [session service override](session-service-override).

> [!WARNING]
> This identity resolver can only be used during the [middleware hook](resolution-hooks#the-middleware-hook),
> as sessions are not initialised until the middleware responsible for it has been run.

## Configuring

When configuring your resolver in the [multitenancy config](configuration#identity-resolvers) you only need
to do one thing to use this identity resolver, set the driver to `session`.
An example config entry for this identity resolver looks like so.

```php
'resolvers' => [
    'session' => [
        'driver' => 'session',
    ],
]
```

You can also optionally provide the name of the session using the `session` config option.
This option allows you to provide placeholders like the other identity resolvers and their route parameters,
and will default to `multitenancy.{tenancy}`.

```php
'resolvers' => [
    'session' => [
        'driver'  => 'session',
        'session' => 'multitenancy.{tenancy}'
    ],
]
```

> [!NOTE]
> When providing a session name, you can use the placeholders `{tenancy}` and `{resolver}`.
> `{tenancy}` will be replaced by the registered name of the current tenancy,
> and `{resolver}` will be replaced by the registered name of the identity resolver.
> The default value is `{tenancy}_{resolver}`.
> You can also use `{Tenancy}`
> and `{Resolver}` which will run the value through `ucfirst()` to uppercase the first letter, as well as dot notation.

## Using

To make use of this identity resolver, once configured, either set
the [default resolver](configuration#multitenancy-defaults) to its name.

```php
'defaults' => [
    //...
    'resolver' => 'session',
]
```

Or provide its name as the second argument when registering tenanted routes.

```php
Route::tenanted(function () {
    // Define tenant routes here
}, 'session');
```

The identity resolver itself is the `Sprout\Http\Resolvers\SessionIdentityResolver` class
and has a few additional methods that may be of use.

```php
public function getSessionName(): string

public function getRequestSessionName(Tenancy $tenancy): string
```

The `getSessionName()` method will return the config value `session`,
and the `getRequestSessionName()` will return the session name for the current tenant of the provided tenancy.
