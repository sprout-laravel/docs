---
section: Core Concepts
title: Tenancies
position: 2
slug: tenancies
description:
  Tenancies are a combination of things, they're core concepts within Sprout that encapsulate the logic behind having a current tenant, and it's also a term to refer to the state of there being a current tenant.
---

## Introduction

The terms "tenancy" and "tenancies" have two meanings within Sprout.

1. A tenancy is the state.
   If you're accessing the application as a particular tenant, say using their subdomain, you
   are within their tenancy.
2. A tenancy, or `Tenancy` is an object that represents the configuration for a particular type of tenant.

For this particular section of the documentation, we're focusing more on the second definition,
dealing with a [configured tenancy](configuration#tenancies).
These tenancies are responsible for keeping track of things like the current tenant,
the tenant provider to use, and even how the current tenant was resolved.

Sprout supports multiple different tenancies,
which means you can a multitenanted application with multiple types of tenants,
either entirely separate from one another or nested.
Have a tenant that itself has multiple child tenants is definitely a rare use case, but it is supported nonetheless.

## How they work

Every entry in the [`tenancies` section of the multitenancy config](configuration#tenancies) is a tenancy,
and will be represented by an object that implements the `Sprout\Contracts\Tenancy` interface.
These instances have a single responsibility,
and it's to keep track of the tenancies' state, though this manifests itself in several different ways.

### Reading the current tenant

Tenancies have a handful of methods for "reading" the current tenant from the tenancy,
that is, operations that return data and values without ever actually setting or changing anything.

```php
public function check(): bool;

public function tenant(): ?Tenant;

public function key(): int|string|null;

public function identifier(): ?string;


```

#### Checking for a current tenant

If you need to see if a particular tenancy has a current tenant, you can use the `check()` method.
This method returns `true` if there is a current tenant, and `false` if there isn't.

```php
if ($tenancy->check()) {
    // There's a tenant
} else {
    // There isn't a tenant
}
```

#### Getting the current tenant

If you need to retrieve the current tenant, you can use the `tenant()` method which will return an instance of
`Sprout\Contracts\Tenant` if there is a tenant, or `null` if there isn't.

```php
$tenant = $tenancy->tenant();

if ($tenant === null) {
    // There isn't a tenant
}
```

#### Getting the tenants key or identifier

If you only need to retrieve the tenants' key or identifier, there are two helper methods for this.
The `key()` method returns the tenant key, and the `identifier()` method returns the tenant identifier.
If there is no current tenant, both methods return `null`.

```php
$model->attach($tenancy->key());

route('my.route', ['tenant_subdomain' => $tenancy->identifier()]);
```

### Writing the current tenant

Tenancies also have several methods for "writing" the current tenant, almost entirely centred around setting them.

```php
public function identify(string $identifier): bool;

public function load(int|string $key): bool;

public function setTenant(?Tenant $tenant): static;
```

#### Setting the tenant by identifier

If you want to set the current tenant and all you have is an identifier, you can use the `identify()` method.
This method will return `true` if the current tenant changed, and `false` otherwise.

```php
if ($tenancy->identify($identifier)) {
    // Tenant was set successfully
}
```

The default implementation of this method comes with a few side effects.

- If the resulting tenant ([retrieved by its identifier](tenant-providers#retrieving-tenants)) is `null`, the current
  resolver and hook will be set to `null`.
- Once the tenant has been set, a [`TenantIdentified` event](lifecycle#tenant-identified-event) will be dispatched.

> [!WARNING]
> The default implementation of this method calls `setTenant()` before dispatching the event.

#### Setting the tenant by key

If you want to set the current tenant and all you have is a key, you can use the `load()` method.
This method will return `true` if the current tenant changed, and `false` otherwise.

```php
if ($tenancy->load($key)) {
    // Tenant was set successfully
}
```

The default implementation of this method comes with a few side effects.

- If the resulting tenant ([retrieved by its key](tenant-providers#retrieving-tenants)) is `null`, the current
  resolver and hook will be set to `null`.
- Once the tenant has been set, a [`TenantLoaded` event](lifecycle#tenant-loaded-event) will be dispatched.

> [!WARNING]
> The default implementation of this method calls `setTenant()` before dispatching the event.

#### Setting the tenant

If instead of a tenant key or identifier, you have an instance of your tenant, you can use the `setTenant()` method.
Since this method is a setter, it _should_ always set the current tenant,
therefore, it does return a value to indicate its success.

```php
$tenancy->setTenant($tenant);

// The tenancy **IS** set
```

The default implementation of this method also has a few side effects.

- If the provided value is the same as the current tenant, it will not attempt to write to the property.
- If the provided value is `null`, the current resolver and hook will be set to `null`.
- If the provided value is not `null`, and is not the same as the current tenant, a [
  `CurrentTenantChanged` event](lifecycle#current-tenant-changed-event) will be dispatched.

### How did the current tenant come to be?

As well as providing ways to read and write the tenancies'
state,
these instances provide a handful of methods for storing information about **HOW** the current tenant came to be,
as well as methods to read this.

```php
public function resolvedVia(IdentityResolver $resolver): static;

public function resolvedAt(ResolutionHook $hook): static;

public function resolver(): ?IdentityResolver;

public function hook(): ?ResolutionHook;

public function wasResolved(): bool;
```

When resolving a tenant, the `resolvedVia()` and `resolvedAt()` methods are called,
to store the [identity resolver](identity-resolvers) that was used,
as well as the [resolution hook](resolution-hooks) where it happened.

```php
$identity = $resolver->resolveFromRequest($request, $tenancy);

$tenancy->resolvedVia($resolver)->resolvedAt($hook);
```

You can then access this information using `resolver()` for the [identity resolver](identity-resolvers),
`hook()` for the [resolution hook](resolution-hooks),
and `wasResolved()` for a boolean check to determine whether the current tenant was in fact resolved.

The default implementations of these methods don't have any side effects,
though it should be considered that `wasResolved()` will only return `true` if there's a current tenant,
and an identity resolver.

### Tenancy Options

Tenancies also have [tenancy options](tenancy-options), as covered in that part of the documentation.
With this in mind,
the objects responsible for managing a tenancies' state also come with methods for working with tenancy options.

```php
public function hasOption(string $option): bool;

public function addOption(string $option): static;

public function removeOption(string $option): static;
```

If you want to check if a tenancy has an option, you have `hasOption()`.
If you want to add a new option, you have `addOption()`,
and if you want to remove an option, you have `removeOption()`.
All these methods accept a `string`,
and aren't tied to the `Sprout\TenancyOptions` class, specifically to allow for additional options.

> [!WARNING]
> Tenancy options are treated as simple boolean values, they are present, or they are not.
> Applications will not do anything regarding a tenancy option,
> unless code has been written specifically to make use of their presence, like with the default options.

## How to work with them

Sprout keeps track of each tenancy as it becomes active, treating the most recent tenancy as the current one.
Tenancies are considered active, either when manually set as the current tenancy, or when resolution is attempted.
Even if resolution fails, that tenancy is still active, it just doesn't have a current tenant.

Sprout also provides a handful of different ways to work with tenancies.

### Using an attribute

Sprout comes with a contextual attribute
that you can add to a method/function parameter to have the current tenancy injected.
This attribute is `Sprout\Attributes\CurrentTenancy`, and requires no arguments.

```php
public function __construct(#[CurrentTenancy] Tenancy $tenancy) {
    // Do something here
}
```

### Using the Sprout core

The core Sprout class (`Sprout\Sprout`),
and by extension the [`sprout()` helper](helpers#sprout),
and [`Sprout\Facades\Sprout` facade](facades#sprout) has a handful of self-explanatory methods
for dealing with active tenancies.

```php
public function setCurrentTenancy(Tenancy $tenancy): void;

public function hasCurrentTenancy(): bool;

public function getCurrentTenancy(): ?Tenancy;

public function getAllCurrentTenancies(): array;
```

If you want to manually set the current tenancy (append the tenancy stack), you use `setCurrentTenancy()`.
If you want to check if there is a current tenancy, you use `hasCurrentTenancy()`,
and if you want to get the current tenancy, or all tenancies,
you use `getCurrentTenancy()` and `getAllCurrentTenancies()` respectively.

> [!WARNING]
> Tenancies are not marked as the "current tenancy" automatically,
> **UNLESS** they're going through Sprouts built-in resolution process.
> If you're doing something that requires a current tenancy,
> but doesn't resolve the tenant, you will have to manually set the current tenancy.

### Using the tenancy manager

Tenancy instances are created and handled by a class known as the "Tenancy Manager".
The tenancy manager is bound to container as a singleton using its class `Sprout\Managers\TenancyManager`,
so is available for injection.
It is also aliased as `sprout.tenancies` if you need to access it that way.
The `Sprout\Sprout` class will always contain the instance of this class,
so it can be accessed via that, the [helper](helpers#sprout) or the [facade](facades#sprout).

```php
// From the Sprout core class
app(Sprout\Sprout::class)->tenancies();

// From the Sprout helper
sprout()->tenancies();

// From the facade
Sprout\Facades\Sprout::tenancies()
```

Full usage of the tenancy manager is an advanced topic covered in another part of the documentation,
but for our purposes here, we only need to worry about the `get()` method.

```php
public function get(?string $name = null): Tenancy
```

This method will retrieve an already instantiated tenancy,
instantiate a new tenancy, or throw an exception if there's a problem.
You can give it the name
used in the [tenancy config](configuration#tenancies) to register a particular type of tenancy,
or leave it `null` to use the [default tenancy](configuration#multitenancy-defaults).
This method is also accessible as a [helper](helpers#tenancy).

```php
function tenancy(?string $name = null): Tenancy;
```
