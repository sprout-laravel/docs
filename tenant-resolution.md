---
description: Tenant resolution is a big part of multitenancy, as it's the part that handles the identification
  and loading of the current tenant. Sprout comes with a number of different options for how to go about this.
---

> [!CALLOUT]
> The documentation is still in progress, and this page is not yet complete.
> Please check back again in the future.

## Introduction

Tenant resolution is the process of determining the current tenant, and then resolving it before setting it as the
actual current tenant.
Within Sprout, there are two paths for tenant resolution, identifying, and loading.

## Identifying Tenants

Tenant identification is a process that uses the [tenant identifier](tenants#the-tenant-identifier) to identify the
tenant.
This is the automated process that makes sure that the correct tenant is available when processing your routes.
To achieve this, an [identity resolver](#) is used, which is a class that is responsible for extracting a tenants'
identifier from an incoming HTTP request.
These resolvers are configured using the [`multitenancy.resolvers`](configuration#identity-resolvers) configuration
option.
These classes also know how to configure a route to make the best use of themselves.

### Tenant Routes

Your multitenanted application will have routes that require a current tenant, which is what we refer to as tenant
routes, because they belong to the tenant.
There are two ways to define tenant routes, but both allow you to set two optional parameters, which follow the same
rules as [middleware parameters](https://laravel.com/docs/11.x/middleware#middleware-parameters).

- The resolver — The name of a resolver registered in the
  [`multitenancy.resolvers`](configuration#identity-resolvers) config.
- The tenancy — The name of a tenancy registered in the
  [`multitenancy.tenancies`](configuration#tenancies) config.

#### Automatic Routes

The best way to define tenant routes is using
the [router macro](https://github.com/sprout-laravel/sprout/blob/1.x/src/Http/RouterMethods.php#L34) that's used in the
[installation guide](installation#registering-tenant-routes).
These routes are defined like this.

```php
// Default
Route::tenanted(function () {
    // Tenant Routes
});

// Default tenancy, manual resolver
Route::tenanted(function () {
    // Tenant Routes
}, 'subdomain');

// Default resolver, manual tenancy
Route::tenanted(function () {
    // Tenant Routes
}, null, 'tenants');

// Manual resolver, manual tenancy
Route::tenanted(function () {
    // Tenant Routes
}, 'subdomain', 'tenants');
````

#### Manual Routes

Sometimes you need to manually specify a route as being tenanted, and in those cases all you need is the `sprout.
tenanted` middleware, which is an alias for `Sprout\Http\Middleware\SproutTenantContextMiddleware`.
You can provide the middleware like this.

```php
// Default
Route::middleware(['sprout.tenanted'])->get('/', '');

// Default tenancy, manual resolver
Route::middleware(['sprout.tenanted:subdomain'])->get('/', '');

// Default resolver, manual tenancy
Route::middleware(['sprout.tenanted:,tenants'])->get('/', '');

// Manual resolver, manual tenancy
Route::middleware(['sprout.tenanted:subdomain,tenants'])->get('/', '');
```

### Available Identity Resolvers

#### Subdomain

#### Header

#### Path

#### Cookie

#### Session

## Loading Tenants
