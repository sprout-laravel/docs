---
section: Core Concepts
title: Tenant Providers
position: 1
slug: tenant-providers
description:
  Every multitenanted application needs a way to retrieve instances of its tenants, regardless of where and how the tenant is stored. This is where the tenant provider comes in.
---

## Introduction

One of Sprouts aims is to support all the different ways that people may build multitenanted applications,
which cannot be achieved without a few abstractions.
Tenant providers are one of these abstractions,
as they abstract out the logic of retrieving instances of your [tenant][1] class,
without understanding how they're stored.

Tenant providers are based on user providers,
a feature of Laravel's native auth functionality
that abstracts away the logic of retrieving authenticated users from their storage medium.
In fact, the two types of classes are very, very similar.

## How they work

Tenant providers are one of the simplest concepts within Sprout.
They exist to provide tenants, and each one knows how to deal with a particular storage medium.
They can provide tenants using a [tenant identifier][2],
[tenant key][3], or, a [resource key][4] if the tenant implements
`TenantHasResources`.

### Retrieving Tenants

All tenant providers implement the `Sprout\Contracts\TenantProvider` interface, so will have three methods for
retrieving tenants, one for each type of value used to identify the tenant.

```php
public function retrieveByIdentifier(string $identifier): ?Tenant;

public function retrieveByKey(int|string $key): ?Tenant;

public function retrieveByResourceKey(string $resourceKey): (Tenant&TenantHasResources)|null;
```

The implementation of these methods will depend entirely on which storage medium the provider is for,
but there is some commonality between them.
In most cases, the methods will use their corresponding name method from the interface
(`getTenantIdentifierName`, `getTenantKeyName` and `getTenantResourceKeyName`).
What the value returned by these methods means will again depend on what the provider is dealing with.
In cases where the provider is dealing with some form of persisted storage,
like a relational database or document store, it'll refer to a column or field within the table or document.

### Boolean Logic

A core idea behind the tenant providers is that they must remain simple and operate using simple boolean logic.
A tenant is either found or not, with no exceptions or extra conditions.
Because of how Sprout is built,
there are points between a tenant being retrieved and the tenant being set,
where you can check any extra conditions you may have.

The only exception to this,
is the `retrieveByResourceKey` method
which should throw a [misconfigured exception][5]
if the `Tenant` implementation does not also implement the `TenantHasResources` interface.

## Provided Implementations

Sprout includes two implementations of the tenant provider, though there are plans for more in the future.

### Eloquent

The Eloquent tenant provider is for applications whose tenants are Eloquent models.
In most use cases, this will be the only tenant provider you need.
You can read more about this tenant provider [here][6].

### Database

The database tenant provider is for applications that want to use Laravel's query builder without Eloquent.
It does require a class as a `Tenant` implementation, but Sprout comes with a default one.
You can read more about this tenant provider [here][7].

## I need something else

While the provided implements should suffice for most people,
there will be some that don't want to use Laravel's query builder or Eloquent.
For these situations, you can create yourself a [custom tenant provider][8].

[1]:    tenants

[2]:    tenants#tenant-identifiers

[3]:    tenants#tenant-keys

[4]:    tenants#tenant-resource-keys

[5]:    exceptions#misconfigured

[6]:    eloquent-tenant-providers

[7]:    database-tenant-providers

[8]:    custom-tenant-provider
