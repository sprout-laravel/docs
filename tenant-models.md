---
section: Eloquent
title: Tenant Models
position: 0
slug: tenant-models
description:
  When working with Laravel, you're almost always going to be working with Eloquent, and tenants are no different.
---

> [!WARNING]
> This page assumes that you've read the [tenants documentation](tenants)
> and are familiar with the concept of tenants within Sprout.

## Introduction

Every multitenanted application needs a tenant,
and if you're using Laravel, you're probably using Eloquent,
so it stands to reason that your tenant will be an Eloquent model.
If this is the case,
you'll be pleased to know that Sprout comes with a handful of functionality to make creating tenants that are Eloquent
models as simple as can be.

## Creating the tenant model

The first thing you're going to want to do is create your model,
whether you do that manually or using the `make:model` artisan command.
Once you've got this done, you'll want to implement the `Sprout\Contracts\Tenant` interface.

```php
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Sprout\Contracts\Tenant;

class Blog extends Model implements Tenant
{
    protected $table = 'blogs';

    protected $fillable = [
        'name',
        'identifier',
    ];
}
```

Now, rather than implement all the methods required by the `Sprout\Contracts\Tenant` class manually,
you can make use of the `Sprout\Database\Eloquent\Concerns\IsTenant` trait.

```php
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Sprout\Contracts\Tenant;
use Sprout\Database\Eloquent\Concerns\IsTenant;

class Blog extends Model implements Tenant
{
    use IsTenant;

    protected $table = 'blogs';

    protected $fillable = [
        'name',
        'identifier',
    ];
}
```

And there you have it, a fully working tenant model.
Remember
to add it to the [tenancies config](configuration#tenancies)
using the [eloquent tenant provider](eloquent-tenant-providers).

### Tenant models with Resources

If your [tenant has resources](tenants#tenants-with-resources),
you'll also want to implement the `Sprout\Contracts\TenantHasResources` interface.

```php
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Sprout\Contracts\Tenant;
use Sprout\Contracts\TenantHasResources;
use Sprout\Database\Eloquent\Concerns\IsTenant;

class Blog extends Model implements Tenant, TenantHasResources
{
    use IsTenant;

    protected $table = 'blogs';

    protected $fillable = [
        'name',
        'identifier',
        'resource_key',
    ];
}
```

Again, rather than implement all the methods manually,
you can use the `Sprout\Database\Eloquent\Concerns\HasTenantResources` trait.

```php
namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Sprout\Contracts\Tenant;
use Sprout\Contracts\TenantHasResources;
use Sprout\Database\Eloquent\Concerns\IsTenant;
use Sprout\Database\Eloquent\Concerns\HasTenantResources;

class Blog extends Model implements Tenant, TenantHasResources
{
    use IsTenant, HasTenantResources;

    protected $table = 'blogs';

    protected $fillable = [
        'name',
        'identifier',
    ];
}
```

## How it works

While [the above code](#creating-the-tenant-model) will work fine,
and will be all that's required for the majority of use cases,
there will be times when you need to customise it.
To do this, you'll need to understand what's happening.

### The tenant identifier

When using this trait,
it is assumed that the [tenant identifier](tenants#tenant-identifiers) is called `identifier`,
as provided by the `getTenantIdentifierName()` implementation.
The `getTenantIdentifier()` method actually makes use of the name method,
so if you need to change the attribute used as an identifier, just override the `getTenantIdentifierName()` method.

```php
public function getTenantIdentifier(): string
{
    return $this->getAttribute($this->getTenantIdentifierName());
}
    
public function getTenantIdentifierName(): string
{
    return 'identifier';
}
```

### The tenant key

When using this trait,
the [tenant key](tenants#tenant-keys) functionality piggybacks on
the [primary key functionality provided by Eloquent](https://laravel.com/docs/11.x/eloquent#primary-keys),
so you'll want to make sure that your model is configured correctly.

```php
public function getTenantKey(): int|string
{
    return $this->getKey();
}

public function getTenantKeyName(): string
{
    return $this->getKeyName();
}
```

### The tenant resource key

If your tenant has resources,
and you're using the `Sprout\Database\Eloquent\Concerns\HasTenantResources` trait,
the key is assumed to be contained within the `resource_key` attribute.
Just like with the identifier, if you want to override this,
you only need to override the `getTenantResourceKeyName()` method.

```php
public function getTenantResourceKey(): string
{
    return (string)$this->getAttribute($this->getTenantResourceKeyName());
}

public function getTenantResourceKeyName(): string
{
    return 'resource_key';
}
```

#### Automatic UUIDs

The `HasTenantResources` trait also does one other thing.
When a model is being created (it is being saved but hasn't been sent to the database yet),
if the resource key attribute is `null`, it will be set with a UUID using `Illuminate\Support\Str::uuid()`.

```php
static::creating(static function (Model&TenantHasResources $model) {
    if ($model->getAttribute($model->getTenantResourceKeyName()) === null) {
        $model->setAttribute(
            $model->getTenantResourceKeyName(),
            Str::uuid()
        );
    }
});
```

> [!NOTE]
> If you're manually setting the tenant resource key, this code will never run, so you don't need to worry about it.
