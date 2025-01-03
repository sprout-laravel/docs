---
section: Tenant Providers
menu: Eloquent
title: Eloquent Tenant Provider
position: 0
slug: eloquent-tenant-providers
description:
  You're working with Laravel, so you're most likely going to have be using Eloquent to define your tenant. In that case, you'll also need to make use of the Eloquent tenant provider.
---

## Introduction

The eloquent tenant provider is a driver for Sprouts [tenant provider](tenant-providers) functionality
that allows you to use Eloquent as your [tenant](tenants).
It's a relatively simple tenant provider to configure and use.

## Using

When configuring your provider in the [multitenancy config](configuration#tenant-providers) you only need
to do two things to use this tenant provider.
Set the driver to `eloquent` and provide a model using the `model` config option.
An example config entry for this tenant provider looks like so.

```php
'providers' => [
    'tenants' => [
        'driver' => 'eloquent',
        'model'  => MyModel::class,
    ],
]
```

When the eloquent tenant provider is created, the model class will be validated and must meet the following criteria.

- It **MUST** exist at runtime.
- It **MUST** implement the `Sprout\Contracts\Tenant` interface, either directly or indirectly.
- It **MUST** be a descendant of the `Illuminate\Database\Eloquent\Model` class.

If the provided value does not meet these criteria,
a [misconfiguration exception](exceptions#invalid-config) will be thrown.

> [!TIP]
> For more information about creating a tenant model, check out the [tenant models](tenant-models) config entry.
