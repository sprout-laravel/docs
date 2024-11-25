---
section: Core Concepts
title: Tenants
position: 0
slug: tenants
description:
  At the core of every multitenanted application, there are tenants. But what exactly are tenants? What is my tenant, and how does Sprout deal with it?
---

## Introduction

Tenants are classes that [tenancies][1] exist around,
in the same way that Laravel guards exist around a user (authenticatable).
Within Sprout, it is a class that implements the `Sprout\Contracts\Tenant` interface, either directly or indirectly.

## Requirements of a Tenant

For Sprout to function, a tenant must have an identifier and a key.

### Tenant Identifiers

Tenant identifiers are strings that are used externally to specify the tenant.
In the majority of cases, this value will be provided as part of the request,
either as part of the URL or in something like a header or cookie.
There are a handful of rules that an identifier should follow.

- Identifiers **MUST** be unique to the tenant.
- Identifiers **MUST** be safe to be shared publicly.
- Identifiers **SHOULD** be immutable.
- Identifiers **MUST** be safe to be used in a HTTP request.

It stands to reason that you're going to let your users change their identifier,
especially if it's a sluggified version of their company name or something.
In these cases, you'll want to create a mechanism of some sort to handle these changes, with restrictions or rules.

These identifiers exist within Sprout in two parts, as a name and as a value.
These parts are provided by methods on the `Tenant` contract.

```php
public function getTenantIdentifierName(): string;

public function getTenantIdentifier(): string;
```

> [!NOTE]
> This pairing of name and value methods is common within Laravel
> 

### Tenant Keys

Tenant keys are either strings or integers, and they're used internally to specify the tenant.
They are used as database foreign keys, within Laravel's context,
and in any other internal private place where tenants are referred to.
Because of this, they are typically the primary key from the database.
Much like with identifiers, there are a handful of rules that a key should follow.

- Keys **MUST** be unique to the tenant.
- Keys **SHOULD** not be shared publicly.
- Keys **MUST** be immutable.

While identifiers should be immutable, keys must be,
as they're used to reference tenants in persisted storage, making it challenging to update.
There are absolutely possible situations where a tenants' key could change,
but those situations would be so rare and such a big deal that it's something you can handle at the time.

Again, just like with identifiers, tenant keys exist within Sprout as two parts, the name and the value.
These parts are provided by methods on the `Tenant` contract.

```php
public function getTenantKeyName(): string;

public function getTenantKey(): string;
```

## Tenants with Resources

Within Sprout, tenants can be marked as having resources, which essentially just means they store things.
For example,
both the [storage override][2] and [session override][3] require tenants
to be configured for resources if they're using a filesystem.
To have a tenant with resources, the same class that implements the `Tenant` interface, must also implement the
`Sprout\Contracts\TenantHasResources` interface.
These tenants have only requirement, and it's that they have a resource key.

### Tenant Resource Keys

Tenant resource keys are just like tenant identifiers and keys,
but they're used exclusively when dealing with filesystems or file storage.
They also have their own rules.

- Resource keys **MUST** be unique to the tenant.
- Resource keys **MUST** be immutable.
- Resource keys **MUST** be safe to be used within a filesystem.

Immutability is particularly important with something like this,
as in most cases, the resource key is used to create a directory or prefix a path.
If a tenants' resource key changed, you'd have to move and rename many files.

These resource keys exist within sprout as two parts, the name and the value. 
These parts are provided by methods on the `TenantHasResources` contract.

```php
public function getTenantResourceKeyName(): string;

public function getTenantResourceKey(): string;
```

## Tenants and Eloquent

Anything can be a tenant, whether it's an Eloquent model, a simple DTO, or something else.
That being said, however, there's a good chance that you're going to be using Eloquent,
as you are, after all, using Laravel.

If that's the case, you'll want to create a [tenant model][4],
configure the [eloquent tenant provider][5] and create some [tenant child models][6].

[1]:	tenancies
[2]:	storage-service-override
[3]:	session-service-override
[4]:	tenant-models
[5]:	eloquent-tenant-providers
[6]:	tenant-child-models
