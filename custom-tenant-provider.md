---
section: Tenant Providers
menu: Custom
title: Custom Tenant Provider
position: 99
slug: custom-tenant-provider
description:
  While Sprout ships with a handful of sensible tenant providers that'll work for most use-cases, there will be times when you need something else. For those situations, you can create a custom tenant provider.
---

> [!WARNING]
> This page assumes that you've read the [tenant providers documentation](tenant-providers)
> and are familiar with the concept within Sprout.

## Introduction

There are times when an applications' tenants aren't stored in a conventional database,
so can't make use of the [database tenant provider](database-tenant-providers).
There are also times when an application is build using a third-party ORM,
so the [eloquent tenant provider](eloquent-tenant-providers) is also out of the question.
Well, in those cases, your best bet is to build a custom tenant provider.

## Creating the tenant provider

To create yourself a custom tenant provider, go ahead and create yourself a class with a sensible name.
Sprout uses the `TenantProvider` suffix for tenant providers, but it's not required.
Once you have it, go ahead and implement the `Sprout\Contracts\TenantProvider` interface,
so it looks something like this.

```php
class MyTenantProvider implements TenantProvider
{
    //...
}
```

This interface requires three methods used for [retrieving tenants](tenant-providers#retrieving-tenants),
and while the signature should match that of the interface,
the actual methods can do whatever is required, with one exception.
The `retrieveByIdentifier()` and `retrieveByKey()` methods should have
straightforward [boolean logic](tenant-providers#boolean-logic),
returning a `Tenant` or `null`, without throwing exceptions or adding additional restrictions.
The `retrieveByResourceKey()` method **SHOULD** throw a [misconfigured exception](exceptions#misconfigured) if,
and only if, the tenant class not implement the `Sprout\Contracts\TenantHasResources` interface.

The `TenantProvider` interface also has one other method, `getName()`.
This method should return the name that the current instance of the class was registered as.
This value is provided when creating a new instance, typically passed into the constructor with the config.

How your tenant provider receives its config, if any is required,
is up to you, though the convention within Sprout is for individual arguments to be passed into the constructor.

```php
// Sprout\Providers\DatabaseTenantProvider
public function __construct(string $name, ConnectionInterface $connection, string $table, string $entityClass = GenericTenant::class)
{ 
    //...
}

// Sprout\Providers\EloquentTenantProvider
public function __construct(string $name, string $modelClass)
{
    //...
}
```

Whether you validate these values within the constructor will depend on your choices,
though it's best to validate them before they passed in, in the tenant providers creator.

## Registering the tenant provider

Once you've got your custom tenant provider created, you need to register it with the provider manager
(`Sprout\Managers\ProviderManager`), by giving it a name, and a callback to create a new instance.

```php
ProviderManager::register('my-tenant-provider', function (Application $app, string $name, array $config) {
    return new MyTenantProvider;
});
```

The callback,
referred to internally as a custom creator,
will receive an instance of `Illuminate\Contracts\Foundation\Application`,
the name it was registered as, as a `string`, and an `array` of config values retrieved by
the [providers config](configuration#tenant-providers).
As long as this method returns an instance of your custom tenant provider, there are no restrictions or limitations.

### Validating provider config

If you're handling config validation within its creator,
which is recommended, there are a couple of [exceptions](exceptions) that are available to you,
to try and keep things standard.

If an expected/required value is missing, there's a [missing config](exceptions#missing-config) exception,
which can be used like so.

```php
throw MisconfigurationException::missingConfig('model', 'provider', $name);
```

The three arguments are value, type, and name.
The value should contain the config option that's missing.
The type should contain the type of thing that is expecting the config option,
and the name should identify it amongst others of the same type.
Based on the example code contained within this page, this particular exception would have the following message.

```text
The provider [my-tenant-provider] is missing a required value for 'model'
```

If a config value is present, but the provided value has something wrong with it,
there's an [invalid config](exceptions#invalid-config) exception, which can be used like so.

```php
throw MisconfigurationException::invalidConfig('model', 'provider', $name);
```

The three arguments for this method are the same as the previous,
so this code would generate an exception with the following message.

```text
The provided value for 'model' is not valid for provider [my-tenant-provider]
```

## Using the tenant provider

With your tenant provider registered with a name and custom creator, you're ready to actually use it.
All you need to do to make use of it
is to set the `driver` option for your [tenant provider](configuration#tenant-providers) config
to be the name you registered it as.
So for example, the following.

```php
'tenants' => [
    'driver' => 'my-tenant-provider',
],
```
