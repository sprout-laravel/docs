---
section: Eloquent
title: Tenant Child Models
position: 1
slug: tenant-child-models
description:
  When your tenant is an Eloquent model, you'll also have child models that belong to the tenant. Scoping every query on these models to the current tenant is often messy and complex, but fortunately, Sprout has something for that.
---

## Introduction

When users are making use of your application, it's important to ensure
that they only have access to data that,
ignoring application-specific instructions, is available to the current tenant.
If your data is stored in the database,
and possibly represented by an Eloquent model,
you're going
to need to add conditions to the database queries that ensure the data is accessible to the current tenant.
This can be a frustrating process, and it's easy to overlook, accidentally exposing cross-tenant data.

## How it works

Sprout supports two tenant relations,
[one-to-many](https://laravel.com/docs/11.x/eloquent-relationships#one-to-many-inverse)
and [many-to-many](https://laravel.com/docs/11.x/eloquent-relationships#many-to-many).
While the exact specifics depend on the type of relation, both do the following.

- Accessed through a trait
- Adds a global scope to the model
- Adds an observer to the model
- Automatically scopes queries to the current tenant
- Automatically sets the tenant relation when creating new models
- Automatically populates the tenant relation when retrieving models from the database
- Checks that models tenant matches the current tenant when creating or retrieving

> [!WARNING]
> If the model is being accessed outside a [multitenanted context](multitenanted-context),
> the functionality provided by these features will be skipped.

### The tenant relation

Both relations require that the model has a relationship definition to the tenant model,
and that the relation is flagged appropriately.
This can be done by overriding a method, or using a PHP attribute.

If you want to have a method that returns the name of the tenant relation,
you can override the `getTenantRelationName()` method.

```php
public function blog(): BelongsTo
{
    return $this->belongsTo(Blog::class);
}

public function getTenantRelationName(): string
{
    return 'blog';
}
```

If you'd rather use a PHP attribute,
you can add the `Sprout\Attributes\TenantRelation` attribute to the method
that represents the relationship to the tenant.

```php
#[TenantRelation]
public function blog(): BelongsTo
{
    return $this->belongsTo(Blog::class);
}
```

### Optional tenant

Sometimes you'll have models that don't need a tenant, but can have one.
In those cases,
you'll need to implement the marker interface `Sprout\Database\Eloquent\Contracts\OptionalTenant` on your model.

```php
class Post extends Model implements OptionalTenant
{
    //...
}
```

Models with optional tenants can be retrieved and created outside an active tenancy.
Normally, if you attempt to create a new instance of a child model,
or retrieve one from the database,
and there isn't a current tenant, an [exception would be thrown](exceptions#tenant-missing-exception).

> [!NOTE]
> An [exception will still be thrown](exceptions#tenant-mismatch-exception)
> if the model has a different tenant to the current one.

### Ignoring tenant restrictions

If you want to temporarily make a model have an optional tenant,
without implementing the interface, you can also do that.
To disable the restrictions, call the static method `ignoreTenantRestrictions()` on the model,
and then call `resetTenantRestrictions()` once you've finished.

```php
Post::ignoreTenantRestrictions();

$posts = Post::get();

Post::resetTenantRestrictions();
```

Alternatively, if you want to disable restrictions for a single action or operation, you can use the static method
`withoutTenantRestrictions()`.

```php
$posts = Post::withoutTenantRestrictions(function() {
    return Post::get();
});
```

This method disables the restrictions, calls the callback without arguments,
enables the restrictions, and returns the callbacks' return value.

## Belongs to Tenant

If your model belongs to a specific tenant, as in,
it has a relationship to it
using Laravel's [inverse one-to-many](https://laravel.com/docs/11.x/eloquent-relationships#one-to-many-inverse)
relation,
you can add the `Sprout\Database\Eloquent\Concerns\BelongsToTenant` trait.

```php
class Post extends Model
{
    use BelongsToTenant;
    
    //...
    
    #[TenantRelation]
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
```

### Querying a belongs to tenant model

When attempting to query a model that belongs to a tenant,
a where clause will automatically be added for the current tenant.
This call uses the [tenants' key](tenants#tenant-keys),
and the relations foreign key.

### Creating a belongs to tenant model

When attempting to create a new model that belongs to a tenant,
the child model will automatically be associated with the current tenant when attempting to save the model.
If when saving, the tenant relation is already populated,
its current value will be compared to the current [tenants' key](tenants#tenant-keys),
throwing a [tenant mismatch exception](exceptions#tenant-mismatch-exception) if they do not match.

> [!NOTE]
> Because of the way one-to-many relations work with Eloquent,
> this also means the relation will be populated by the tenant model too.

### Retrieving a belongs to tenant model

When retrieving an existing tenant child model from the database,
when the model is being hydrated (the data from the database is populating the model),
the tenant relation will also be populated with the current tenant.
During this process, the models' tenant will be compared to the current tenant,
throwing a [tenant mismatch exception](exceptions#tenant-mismatch-exception) if they do not match.
You can disable this functionality
by removing the [hydrating tenant relations](tenancy-options#hydrating-tenant-relations) tenancy option.

## Belongs to many Tenants

If your model belongs to one or more tenants, as in,
it has a relationship to its tenants
using Laravel's [many-to-many](https://laravel.com/docs/11.x/eloquent-relationships#many-to-many) relation,
you can add the `Sprout\Database\Eloquent\Concerns\BelongsToManyTenants` trait.

```php
class Category extends Model
{
    use BelongsToManyTenants;
    
    //...
    
    #[TenantRelation]
    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class);
    }
}
```

### Querying a belongs to many tenants model

When attempting to query a model that belongs to multiple tenants,
a where clause will automatically be added for the current tenant.
This call uses the [tenants' key](tenants#tenant-keys),
and the [`whereHas()` method](https://laravel.com/docs/11.x/eloquent-relationships#querying-relationship-existence).

### Creating a belongs to many tenants model

When attempting to create a new model that belongs to multiple tenants,
the child model will automatically be associated with the current tenant once the model has been saved.
Unlike the belongs to functionality, the relationship can only be saved once the child model has been saved.

### Retrieving a belongs to many tenants model

When retrieving an existing tenant child model from the database,
when the model is being hydrated (the data from the database is populating the model),
the tenant relation will also be populated with the current tenant.
During this process, the models' tenant will be compared to the current tenant,
throwing a [tenant mismatch exception](exceptions#tenant-mismatch-exception) if they do not match.
You can disable this functionality
by removing the [hydrating tenant relations](tenancy-options#hydrating-tenant-relations) tenancy option.

> [!WARNING]
> This functionality does not query the database
> to ensure that the retrieved models to do in fact belong to the current tenant.
> This should be okay assuming the tenant restrictions were not disabled, and it was queried normally.
> It will also overwrite any value for the relation to only contain the current tenant.
