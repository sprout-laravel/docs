---
section: Core Concepts
title: Tenancy Options
position: 8
slug: tenancy-options
description:
  Tenancy options are a way of modifying the configuration of Sprout per tenancy.
---

## Introduction

Tenancy options are an additional layer of customisation that lets you control a few different things per configured
tenancy.
They're provided in the [`options` config section](configuration#tenancy-options) when configuring a tenancy.

## How they work

The options themselves are arbitrary string values
that are essentially used as flags by any code that can make use of them.
Each option has a pair of methods on the `Sprout\TenancyOptions` class,
one that returns the value, and one that will check if a [tenancy](tenancies) has that option.

There are currently only two tenancy options.

### Hydrating Tenant Relations

This option, whose string value is `tenant-relation.hydrate` controls whether when loading
a [tenant-owned model](tenant-child-models) from the database,
the relation to that model that relates to the tenant should be automatically hydrated with the current tenant.
You can add the option to a tenancy using `TenancyOptions::hydrateTenantRelation()`.

```php
'options' => [
    TenancyOptions::hydrateTenantRelation(),
]
```

### Throw if not related

This options, whose string value is `tenant-relation.strict` controls how strict the handling
of [tenant child models](tenant-child-models) is when it comes to the tenant relation.
If this option is present within a tenancy,
a [tenant mismatch](exceptions#tenant-mismatch) exception will be thrown
if you attempt to create or retrieve a tenant child model who does not belong to the current tenant.
You can add the option to a tenancy using `TenancyOptions::throwIfNotRelated()`.

```php
'options' => [
    TenancyOptions::throwIfNotRelated(),
]
```
