---
section: Core Concepts
title: Identity Resolvers
position: 3
slug: identity-resolvers
description:
  Identity resolvers, as the name suggests, are responsible for resolving identities, but more specifically, the identity of a tenant.
---

## Introduction

Identity resolvers are abstractions of the logic involved in extracting a tenants' identifier from a request.
While a lot of Sprouts functionality is built based on Laravel's auth handling,
identity resolvers do not have a corresponding component.
The closest thing, would be the entire `Guard` class itself.

The primary reason behind having them abstracted in this way
is that they do not rely on a particular [tenancy](tenancies),
[tenant](tenants), or even [tenant provider](tenant-providers), and can be swapped in and out without issue.

### Why would I want to swap them out?

In this context, I don't mean
swapping them out as in changing the primary way your application resolves tenant identities.
What I mean, is that in the process of Sprout running, any identity resolver can be dropped into that place.
You may be using the [subdomain](subdomain-identity-resolvers) identity resolver as your primary resolver,
but you may also have a few API endpoints
that you want to wrap with the [header](header-identity-resolvers) identity resolver.
Sprout lets you use as many as you like, in unison.

## How they work

Identity resolvers have a simple job,
they need to find a string somewhere in the current request that can be used as a tenant identifier.
While that task alone is relatively straight forward,
there are a number of additional bits and pieces that they should do, or be capable of doing.

### Setting up routes

Since identity resolvers make use of the request,
there are most likely going to be things that they want in place when the request comes in,
things that should be defined when defining routes.
Forcing every developer
to know all the requirements and limitations for each identity resolver wouldn't really make for nice a developer
experience,
so instead, the identity resolvers themselves are capable of helping with that.

```php
public function routes(Router $router, Closure $groupRoutes, Tenancy $tenancy): RouteRegistrar;
```

All identity resolvers have a `routes()` method that is called internally by the `Route::tenanted()` helper method.
All implementations of this method should wrap the provided tenanted routes in a route group
that has all the relevant settings and options for the tenant to be resolved using it.
This will include any middleware that's relevant, as well as any possible route parameters.

### Knowing when to resolve

Sprout itself supports three different [lifecycle hooks](configuration#enabled-hooks) where a tenant can be identified.
Some identity resolvers may have limitations over whether it can find an identity during a specific lifecycle hook,
or for a specific type of request.
To achieve this, each identity resolver has a `canResolve()` method, which is always called before any attempts are
made.

```php
public function canResolve(Request $request, Tenancy $tenancy, ResolutionHook $hook): bool
```

A primary example of where this would be best used,
is the [session identity resolver](session-identity-resolvers)
which only works during the [`Middleware`](configuration#resolutionhookmiddleware) hook
and if the current request has a session.

### Resolving the identifier

The next part of the identity resolver is the bit that actually resolves the identity.
All implementations are capable of doing this given only a `Request` object and a [tenancy](tenancies),
using the `resolveFromRequest()` method.

```php
public function resolveFromRequest(Request $request, Tenancy $tenancy): ?string;
```

### Route parameters

Some identity resolvers function between when there's a current route, as they can use route parameters.
This classes also implement the `Sprout\Contracts\IdentityResolverUsesParameters` interface,
and have a `resolveFromRoute()` method.

```php
public function resolveFromRoute(Route $route, Tenancy $tenancy, Request $request): ?string;
```

This method functions in an almost identical way, except it will use a `Route` object as well as a `Request`.

This contract also provides an additional method that lets you retrieve the route parameter name that it expects,
for a given tenancy.

```php
public function getRouteParameterName(Tenancy $tenancy): string;
```

### The setup hook

The final part of an identity resolver is the setup lifecycle hook which is called during the tenancy bootstrapping,
once a tenancy has been identified.
Just like the fact that identity resolvers require routes to be set up in a specific way,
they may also require additional things to be set up in specific ways.
They can do this be handling it within the `setup()` method.

```php
public function setup(Tenancy $tenancy, ?Tenant $tenant): void;
```

The method is used by many of the identity resolvers, but its most common use is with ones that use route parameters.
When this method is called during the tenancy bootstrapping,
the identity resolver will set the default value for the tenant identifier route parameter to be the identifier for the
current tenant.
Without it, you'd have to now only know the name of the route parameter,
but manually provide its name and value; every time you wanted to generate a tenant URL.

## Provided Implementations

Sprout includes a handful of identity resolvers for just about all the different ways you may need.

### Subdomain

The subdomain identity resolver uses the subdomain portion of the requests' host, and uses route parameters.
All the subdomain identity resolver requires to function
is the name of the primary domain that tenant subdomains should exist on.
You can read more about this identity resolver [here](subdomain-identity-resolvers).

### Path

The path identity resolver uses a segment of the URLs path, and will use route parameters if present.
When defining routes, this identity resolver will also set the parameter as the first segment,
so if you want to be another one, you'll need to wrap the routes in a prefixed group.
You can read more about this identity resolver [here](path-identity-resolvers).

### Header

The header identity resolver uses a HTTP header present in the current request to identity the tenant.
Because of limitations of Laravel, this identity resolver does not make use of route parameters.
It does, however, come with a piece of middleware it adds automatically,
so that a tenants' HTTP header is provided on the outgoing response too.
You can read more about this identity resolver [here](header-identity-resolvers).

### Cookie

The cookie identity resolver uses a cookie present in the HTTP request,
expecting its contents to contain a tenant identifier.
Much like with the header identity resolver, this one is also not capable of using route parameters.
It will also make sure that the cookie is present on outgoing responses too.
You can read more about this identity resolver [here](cookie-identity-resolvers).

> [!WARNING]
> There are some potential side effects when using the [cookie identity resolver](cookie-identity-resolvers),
> and the [cookie service override](cookie-service-override) together.
> This is something I plan to look into, which you can check up
> on [here](https://github.com/sprout-laravel/sprout/issues/75).

### Session

The session identity resolver uses a value within the session to identify the tenant.
Unlike all the other identity resolvers,
this one **must** be used during the middleware phase of the request lifecycle,
to guarantee that the session has been loaded.
You can read more about this identity resolver [here](session-identity-resolvers).

> [!WARNING]
> This identity resolver is the most restrictive and limiting part of Sprout.
> Make sure to read its documentation in full so that you understand the potential side effects and incompatibilities.
> It is only included in the spirit of making everything available to everyone,
> I do not imagine it'll be used that often.

## Need something else?

To the best of my knowledge, there's one potential identity resolver missing from this list,
and it's the one that handles full domains.
The domain identity resolver has been planned, and will be added post-launch.
This identity resolver will internally map using subdomains,
so if it's something you'd like to use, stick with subdomains for now.
You can see the task for it [here](https://github.com/sprout-laravel/sprout/issues/64).

If you require an identity resolver that Sprout doesn't provide,
or, you need the identity resolver to work differently, you can always build a [custom one](custom-identity-resolver).
