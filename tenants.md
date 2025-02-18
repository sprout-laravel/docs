---
description: At the core of every multitenanted application is the tenant. Tenants are entities, but, they
  also require supporting functionality to simplify working with them.
---

> [!CALLOUT]
> The documentation is still in progress, and this page is not yet complete.
> Please check back again in the future.

## Introduction

One of the core driving points behind Sprout is for it to be seamless, and allow you to just write code as you normally
would, without having to write multitenancy specific code.
This is no more noticeable than when it comes to tenants, where the use of a simple interface and a little bit of
configuring will have everything up and running.

For Sprout to function, there needs to be a tenant, which is a class that implements the `Sprout\Contracts\Tenant`
interface.
There needs to be a [tenant provider](#) configured to use this tenant, and a [tenancy](#) needs to be set to use the
provider.
However, the whole process for this is no more complex than the steps you can find in the
[installation guide](installation).

> [!NOTE]
> If you're familiar with [Laravels auth](https://laravel.com/docs/11.x/authentication) 
> functionality, 
> you can consider the `Tenant` interface Sprouts version of Laravel's `Authenticatable` interface.

## The Anatomy of a Tenant

Tenants inside Sprout, the specific class that implements the interface, require only two things, which is enforced
by the interface itself.
The first is a unique identifier, which is used as part of a HTTP request to identify the tenant.
The second is a unique key, which is used to identify the tenant internally, from the database and the likes.

### The Tenant Identifier

Tenant identifiers have the following rules:

- They **MUST** be unique for that type of tenant
- They **MUST** be a `string`, or castable to a `string`
- They **SHOULD** be URL safe
- They **CAN** be immutable

The first two rules are pretty clear, identifiers must be unique, otherwise how you would ever identify the correct
tenant, and they must be a string, because almost everything that's part of a HTTP request is one.
The second two, however, are less clear, and they have a few caveats.

Having the identifier be URL safe is recommended, though the actual definition of "URL safe" depends entirely on how
you're identifying tenants.
If you're using subdomains, then the identifier must be a valid subdomain.
If you're using the URL path, then the identifier must be valid for a path segment.

Immutability is the same, it's recommended, but again depends on other factors.
If you're using part of the URL to identify your tenants, the tenants themselves are probably going to want control
over that, so it can't be immutable.
In situations where you're allowing users to control their identifiers, keep these things in mind:

- There will be terms you don't want them using, especially if you're using subdomains (`www`, `official`, `mail`, etc.)
- You'll want to make sure that identifiers cannot be reused immediately, to avoid impersonation, fraud, etc.
- You'll also want to handle redirects for a while after the identifier is changed, to avoid breaking links

### The Tenant Key

Tenants keys have the following rules:

- They **MUST** be unique for that type of tenant
- They **MUST** be a `string`, `int`, or castable to those
- They **MUST** be immutable

Tenant keys are used internally, as database foreign keys and the likes, so it's important that they be unique
and immutable, because no one wants to update hundreds or thousands of records to change a key.
Because of how these keys are used, they will almost always be the primary key of a database table, and so they
will be an `int` by default with Laravel, but they can also be a `string` for things like UUIDs or ULIDs.

## Creating a Tenant

As you will have seen from the ["creating your tenant"](installation#creating-your-tenant) section of the installation
guide, tenants are very easy to create, especially when using Eloquent.
That being said, there will be times when Eloquent isn't used, or you want to override something, so let's take a look
at what the interface actually adds.

```php
public function getTenantIdentifier(): string;

public function getTenantIdentifierName(): string;

public function getTenantKey(): int|string;

public function getTenantKeyName(): string;
```

I've stripped out the docblocks, but even without them the methods are pretty self-explanatory.
The `getTenantIdentifier` method returns the tenants' identifier, whereas `getTenantIdentifierName` returns it name,
which would be the attribute, or column name that stores the identifier.
The same goes for `getTenantKey` and `getTenantKeyName`, but for the tenant key instead.

### Tenant Models

If you're using Eloquent, you can drop in the `Sprout\Database\Eloquent\Concerns\IsTenant` trait, which adds default
implementations for all these methods.
It assumes that the tenant identifier is stored in `identifier`, and it piggybacks off
Larvels [primary key](https://laravel.com/docs/11.x/eloquent#primary-keys) functionality for the tenant key.
If you wish to use a different attribute for the tenant identifier, you only need to override the
`getTenantIdentifierName` method and returns its name.
Tenant models should be paired with the [eloquent tenant provider](#eloquent-tenant-provider).

### Non-Eloquent Tenants

Tenants can be anything within Sprout, though how their data is read and written will be different, and will require
a custom [tenant provider](#).
Unless you want to use the database without Eloquent.
In that case, Sprout comes with a
[`GenericTenant` class](https://github.com/sprout-laravel/sprout/blob/1.x/src/Support/GenericTenant.php), that can be
used as your tenant, or form the base of it.
If you wish to use the database directly, you'll want to use the [database tenant provider](#database-tenant-provider).

> [!TIP]
> You can provide an Eloquent model class for the `table` option, and the database driver will use the models
> table.
> This exists to allow you to access tenants without the overhead of Eloquent.

### Tenants with Resources

Some features within Sprout require that a tenant has resources, which is mostly used for anything to do with files
and filesystems.
These features require that the tenant also implements the `Sprout\Contracts\TenantHasResources` interface, which
requires that a tenant has a resource key.
Tenant resource keys have the following rules:

- They **MUST** be unique for that type of tenant
- They **MUST** be a `string`, or castable to a `string`
- They **MUST** be immutable
- They **MUST** be safe for use in file paths

Tenant resource keys are used as part of a file path, whether it's a directory or file, so they should be safe to be
used like this, and like with the tenant identifier, they must be unique and a `string`.
Similarly to the tenant key, tenant resource keys must be immutable because you don't want to be mass-renaming files,
and moving directories around.

```php
public function getTenantResourceKey(): string;

public function getTenantResourceKeyName(): string;
```

#### Eloquent Tenants with Resources

If you're using Eloquent, you can use the`Sprout\Database\Eloquent\Concerns\HasTenantResources` trait, which does
the following:

- Defines the resource key as being named `resource_key` (Inside `getTenantResourceKeyName`)
- Adds a listener to the model `creating` event, setting the resource key to
  a [UUID](https://laravel.com/docs/11.x/strings#method-str-uuid) if it's not set

## Tenant Providers 

While any class can be a tenant within Sprout, every type of tenant needs a tenant provider that is capable of 
working with it.
These providers do not save or store tenants, they simply retrieve them, and they can do this in one of three
different ways.

- Via a tenant identifier
- Via a tenant key
- Via a tenant resource key

It's unlikely that the majority of users will need to do anything with a tenant provider beyond the initial
[configuration](configuration#tenant-providers).
However, there may come a time when you need to write one yourself.

> [!CALLOUT]
> 

### Eloquent Tenant Provider

The Eloquent tenant provider is most likely to be the only one you'll ever need; after all, we're working with Laravel.
The provider itself does nothing fancy, it simply creates a new query from the model class its given, and then queries
it using the appropriate name method (`getTenantKeyName`, `getTenantIdentifierName`, `getTenantResourceKeyName`), and
the value its given.

If you wish to this provider, you'll want to make sure that you're using the `eloquent` driver for the
[tenant provider config](configuration#tenant-providers), with your tenant model set as the `model` option.

```php
'providers' => [
    'tenants' => [
        'driver' => 'eloquent',
        'model'  => \App\Models\Blog::class,
    ],
],
```

### Database Tenant Provider

If you want to go down this route, you'll need to make sure you're using the `database` driver for the
[tenant provider config](configuration#tenant-providers), which also has the following config options.

- `table` - The name of the database table that stores the tenants. This is required
- `entity` - The class, who represents the tenant. This is optional and will default to the `GenericTenant`
- `connection` - The configured [database connection](https://laravel.com/docs/11.x/database) to use. This is
  optional and will default to using `database.default`

```php
'providers' => [
    'tenants' => [
        'driver'     => 'database',
        'table'      => 'blogs',
        'entity'     => BlogEntity::class,
        'connection' => 'core',
    ],
],
```
