---
description: At the core of every multitenanted application is the tenant. Tenants are entities, but they
  also require supporting functionality to simplify working with them.
---

## Introduction

One of the core driving points behind Sprout is for it to be seamless, and allow you to just write code as you normally
would, without having to write multitenancy specific code.
This is no more noticeable than when it comes to tenants, where the use of a simple interface and a little bit of
configuring will have everything up and running.

For Sprout to function, there needs to be a tenant, which is a class that implements the `Sprout\Contracts\Tenant`
interface.
There needs to be a [tenant provider](#tenant-providers) configured to use this tenant,
and a [tenancy](#tenancies) needs to be set to use the provider.
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
a custom [tenant provider](#tenant-providers).
Unless you want to use the database without Eloquent.
In that case, Sprout comes with a
[`GenericTenant` class](https://github.com/sprout-laravel/sprout/blob/1.x/src/Support/GenericTenant.php) entity that
acts as your tenant implementation, or forms the base of it.
If you wish to use the database directly, there's the [database tenant provider](#database-tenant-provider).

> [!TIP]
> You can provide an Eloquent model class for the `table` option, and the database driver will use the models
> table.
> This exists to allow you to access tenants without the overhead of Eloquent.

### Tenants with Resources

Some features within Sprout require that a tenant has resources, which is mostly used for anything to do with files
and filesystems.
These features require that the tenant also implements the `Sprout\Contracts\TenantHasResources` interface, which
requires that a tenant has a resource key.
These keys have the following rules:

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

> [!NOTE]
> If you want to find out more about tenant providers, such as how they work, how to interact with them, and how
> to create your own, you can check out the [tenant providers documentation](tenant-providers).

### Eloquent Tenant Provider

The Eloquent tenant provider is most likely to be the only one you'll ever need; after all, we're working with Laravel.
The provider itself does nothing fancy, it simply creates a new query from the model class its given, and then queries
it using the appropriate name method (`getTenantKeyName`, `getTenantIdentifierName`, `getTenantResourceKeyName`), and
the value its given.

If you wish to use this provider, you'll want to make sure that you're using the `eloquent` driver for the
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

## Tenant Children

Tenant children are entities within your application that belong to a tenant.
If `Blog` was your tenant, than `Post` and `Category` would be tenant children, as they both belong to a `Blog`.
Sprout comes with supporting functionality that simplifies and automates the process of using Eloquent models as
tenant children.
When creating these models, you can implement one of two traits, depending on how it relates to the tenant.

- `Sprout\Database\Eloquent\Concern\BelongsToTenant` - The model belongs to a single tenant.
- `Sprout\Database\Eloquent\Concern\BelongsToManyTenants` - The model belongs to many tenants.

Once these traits have been added, all you need to do is use the `Sprout\Attributes\TenantRelation` attribute to mark
a relationship as the tenant relation.

```php
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concern\BelongsToTenant;

class Post extends Model
{
    use BelongsToTenant;
    
    #[TenantRelation]
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
```

Adding this trait to your child models will have the following effects, _if_ within the [multitenanted context](#).

- All read queries for that model, made while there's an active tenant, will automatically be scoped to the current
  tenant.
- Creating and saving a new model, while there's an active tenant, will cause that model to automatically be
  associated with the current tenant.

There are also two other things that will happen, but whether each happens depends on the
[configured tenancy options](configuration#tenancy-options).

- [Throw if not Related](#throw-if-not-related) - If this tenancy option is enabled, an exception will be thrown when a
  model is created from the database, if it belongs to a tenant other than the current active one.
- [Hydrate Tenant Relation](#hydrate-tenant-relation) - If this tenancy option is enabled, the tenant relation will be
  populated with the current tenant, assuming that the model belongs to it.

### Avoiding Restrictions

If you wish to avoid the restrictions, like having queries automatically scoped and model ownership validated, there
are a handful of helper methods to help with this.
These methods are called on any child model, but they affect the restrictions of all child models.
The functionality is similar to Laravel's transaction functionality, so there are two options.
The first is a pair of methods, one that disables the restrictions, and one that resets the restrictions.

```php
Post::ignoreTenantRestrictions();

$posts = Post::all();

Post::resetTenantRestrictions();
```

The second option for this allows you to wrap everything that needs to avoid restrictions within a callback.
Whatever your callback returns will be returned by the helper method.

```php
$posts = Post::withoutTenantRestrictions(function () {
    return Post::all();
});
```

> [!WARNING]
> The restrictions and processes put in place by the tenant child model functionality helps keep tenant data separate.
> Avoiding it may cause tenant data to leak, making it accessible to other tenants, so please be careful in its usage.

### Optional Children

Sometimes you'll want to have models that can belong to a tenant, or tenants, but can also belong to none, making the
relationship optional.
Take the `Blog` example.
It makes sense that a `Post` would belong to a `Blog`, and it makes sense that a `Category` would also belong to a
`Blog`.
But, what if the application provided a handful of default categories that every blog has access to.

To allow for this, you can add the `Sprout\Database\Eloquent\Contracts\OptionalTenant` interface to your model.
This interface is known as a "marker interface", as its only used to mark a model as having its tenant relation be
optional.

```php
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concern\BelongsToTenant;
use Sprout\Database\Eloquent\Contracts\OptionalTenant;

class Category extends Model implements OptionalTenant
{
    use BelongsToTenant;
    
    #[TenantRelation]
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
```

Marking a model in this way will have the following effects.

- The tenant relation **will not** be automatically populated.
- An exception **will not** be thrown when hydrating the model if the model has no tenant.
- The tenant relation **will not** be populated if the model has no tenant.
- Read queries **will** be automatically scoped to the current tenant, **and** `null`.

> [!WARNING]
> This means that you **MUST** manually set the tenant relation.
> In the example this would
> involve [associating](https://laravel.com/docs/11.x/eloquent-relationships#updating-belongs-to-relationships) the
`Category` model with an existing `Blog` model, using the
> `blog()` relation.

### Non-Eloquent Children

Out-of-the-box Sprout only supports using Eloquent and the database directly, and since the database functionality of
Laravel does not allow for the same sort of usage, it's not possible for Sprout to automate using it.
However, if you're using a third-party (or maybe first-party) addon that adds support for something like another ORM,
it is likely that will come with supporting functionality, and you'd need to look at its documentation for details.

## Tenancies

Tenancies are Sprouts version of Laravel's auth guards.
Auth guards are responsible for locating and retrieving the current user, as well as keeping track of them, and Sprouts
tenancies do the same, but for tenants rather than users.
These tenancies are defined in [the `multitenancy.tenancies` configuration](configuration#tenancies), and just like
auth guards, you can have more than one.

> [!WARNING]
> While you can currently have multiple tenancies within your application, the behaviour of both Sprout and Laravel
> is uncertain when layering tenancies (having subtenancies).
> This is something that [will be looked into](https://github.com/sprout-laravel/sprout/issues/106).

Tenancies are always instances of `Sprout\Contracts\Tenancy`, and Sprout ships with only one implementation,
`Sprout\Support\DefaultTenancy`, so unless you're using an addon or custom implementation, this will be the class
used.
There are a handful of ways to retrieve the current tenancy, using a
[contextual attribute](https://laravel.com/docs/11.x/container#contextual-attributes), a facade, or a helper function.

```php
// Using dependency injection
public function __construct(#[CurrentTenancy] Tenancy $tenancy) {}

// Using facades
Sprout::getCurrentTenancy()

// Using helper methods
sprout()->getCurrentTenancy();
```

> [!NOTE]
> If you want to find out more about tenancies, such as how they work, how to interact with them, and how
> to create your own, you can check out the [tenancy documentation](tenancies).

### Tenancy Options

Sprout allows you some control over the behaviour of tenancies using tenancy options.
These options all come from the `Sprout\TenancyOptions` class, and can be added to the `options` part of the
[tenancy config](configuration#tenancy-options).

#### Hydrate Tenant Relation

This option will enable the hydration of the tenant relation when retrieving child models.
When enabled, the relation is set to the current tenant, and marked as loaded, without the need to query and retrieve
a new model instance.

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::hydrateTenantRelation(),
    ],
],
```

#### Throw if not Related

This option is also to do with child-model functionality, and tells the tenancy whether or throw an exception if the
model being retrieved does not belong to the current tenant.
It is recommended that this option be kept in, as it can help avoid cross-tenant data leaking.

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::throwIfNotRelated(),
    ],
],
```

#### All Overrides

This option tells Sprout that all the configured [service overrides](service-overrides) should be enabled and used for
this tenancy.

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::allOverrides(),
    ],
],
```

#### Overrides

This option allows you to specify exactly which [service overrides](service-overrides) should be enabled and used
for the tenancy.
It takes an `array` of the service overrides name, as registered in the
[`sprout.core.overrides` config](configuration#service-overrides).

```php
'tenants' => [
    'provider' => 'tenants',
    'options'  => [
        TenancyOptions::hydrateTenantRelation(),
        TenancyOptions::throwIfNotRelated(),
        TenancyOptions::overrides([
            'job', 'filesystem', 'cache'
        ]),
    ],
],
```

## Working with Tenants

Sprout has been built to be seamless, so beyond the initial configuration and setting up, you're unlikely to encounter
much, beyond the odd import of a Sprout class here and there.
That being said, there are a few things that you'll need to know to work with tenants.

### Tenant Context

A number of Sprouts features, such as the `CurrentTenancy`and `CurrentTenant` attributes, will **only work**
while inside a [multitenanted context](#), with some going as far as to throw an exception if outside one.
Out-of-the-box there are two possible places that Sprout will consider a multitenanted context.

- When handling a [tenant route](tenant-resolution#tenant-routes).
  Everything beyond the first attempt to identify a tenant for the route is within
  the context.
- When processing a job while the [job service override](service-overrides#jobs) is enabled.

### Getting the Current Tenant

Since most of the functionality around tenants is automatic and happens in the background, the tenant itself isn't
exposed to your code, though it is available.
The best way for you to get the tenant, is to use the
[contextual attribute](https://laravel.com/docs/11.x/container#contextual-attributes),
`Sprout\Attributes\CurrentTenant`.
As with all contextual attributes, it doesn't care about the type of the parameter, so you can safely typehint your
tenant model.

```php
public function __construct(#[CurrentTenant] Blog $blog) {
    $this->blog = $blog;
}
```

> [!WARNING]
> Sprout does not create a binding for the class you use as a tenant, so type hinting it without the `CurrentTenant`
> attribute will not work unless you've added something yourself to support it.
