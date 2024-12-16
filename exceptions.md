---
section: Core Concepts
title: Exceptions
position: 7
slug: exceptions
description:
  Sprout comes with a handful of custom exceptions, and for the most part, you can identify what's wrong just by the type.
---

## Introduction

All of Sprout's exceptions extend a common class, `Sprout\Exceptions\SproutException`.
If you wish to log or silence all Sprout exceptions, you can use this as the value.
Below you'll find a breakdown of the different exception types, what they mean and how they're used.

## Compatibility Exception

Sometimes there are components that don't work well together, and have to throw an exception if they're both present.
In these situations, a `Sprout\Exceptions\CompatibilityException` will be thrown, with the following message.

```text
Cannot use {type} [{name}] with {type} [{name}]
```

For example, the [session identity resolver](session-identity-resolvers) and
the [session service override](session-service-override) are not compatible,
so attempting to have both enabled will throw an exception with the following message.

```text
Cannot use resolver [resolver-name] with override [Sprout\Overrides\SessionOverride]
```

## Misconfiguration Exception

The `Sprout\Exceptions\MisconfigurationException` is an exception class that is used in a number of different ways,
but always relating to a configuration issue.

### Missing Config

If your config is missing a required value, you'll receive one of these exceptions, with a message like the following.

```text
The {type} [{name}] is missing a required value for '{value}'
```

For example,
if you're missing the model option for the [eloquent tenant provider](eloquent-tenant-providers),
this exception will be thrown with the following message.

```text
The provider [provider-name] is missing a required value for 'model'
```

### Invalid Config

If your config value isn't valid, you'll receive one of these exceptions, with a message like the following.

```text
The provided value for '{value}' is not valid for {type} [{name}]
```

For example,
if you were to provide the model option for the [eloquent tenant provider](eloquent-tenant-providers),
but, the value that you provided is either not a valid model, or a tenant,
you'd get one of these exceptions with the following message.

```text
The provided value for 'model' is not valid for provider [provider-name]
```

### Misconfigured

This exception is currently only thrown
if you attempt
to use your [tenant](tenants) with something that requires [tenant resources](tenants#tenants-with-resources),
but the tenant entity doesn't implement the correct interface.
This will give you an error message like the following.

```text
The current tenant [You\Tenant\Class] is not configured correctly for resources
```

### Not Found

This exception is thrown in circumstances where something that should exist could not be found.
The message looks like the following.

```text
The {type} for [{name}] could not be found
```

It is currently used for the various manager/factory classes
([identity resolvers](identity-resolvers), [tenant providers](tenant-providers) and [tenancies](tenancies))
in the following circumstances:

- The manager/factory attempts to call a custom creator, but one does not exist
- The manager/factory cannot find a config entry for the identity resolver, tenant provider or tenancy
- The manager/factory cannot find a creator for the identity resolver, tenant provider or tenancy

An example of the error message is as follows.

```text
The custom creator for [provider::custom-provider] could not be found
```

### No Default

This exception is thrown when one of the manager/factory classes
([identity resolvers](identity-resolvers), [tenant providers](tenant-providers) and [tenancies](tenancies))
is attempting to resolve an instance,
has been given no name, and no default is configured.
This message looks like the following.

```text
There is no default {type} set
```

An example of the error message is as follows.

```text
There is no default provider set
```

### Unsupported Hook

This exception is thrown when attempting to resolve an identity from a request,
using a [resolution hook](resolution-hooks) that is not supported (present in
the [hooks config](configuration#enabled-hooks)).
The message looks like the following.

```text
The resolution hook [{hook}] is not supported
```

An example of the error message is as follows.

```text
The resolution hook [Booting] is not supported
```

## No Tenant Found

When attempting to resolve a tenant for a tenanted-route,
if no tenant could be found, a `Sprout\Exceptions\NoTenantFoundException` will be thrown.
The message looks like the following.

```text
No valid tenant [{tenancy}] found [{resolver}]
```

An example of the error message is as follows.

```text
No valid tenant [tenants] found [subdomain]
```

## Tenancy Missing

Some operations within Sprout require a current tenancy,
and if one cannot be found, a `Sprout\Exceptions\TenancyMissingException` will be thrown with the following message.

```text
There is no current tenancy
```

This exception is primarily used by overrides and the tenant-aware drivers that they add,
but it can be used anywhere within Sprout.

## Tenant Mismatch

When attempting
to save or retrieve a [tenant child model](tenant-child-models)
whose tenant relation is set to a different tenant to the current one,
a `Sprout\Exceptions\TenantMismatchException` will be thrown with a message like the following.

> [!NOTE]
> If the [tenancy option "Throw if not related"](tenancy-options#throw-if-not-related) is not set,
> no exception will be thrown.

```text
Model [{model}] already has a tenant, but it is not the current tenant for the tenancy {?[{tenancy}]}
```

An example of the error message is as follows.

```text
Model [Your\Tenant\Child\Model] already has a tenant, but it is not the current tenant for the tenancy [tenants]
```

> [!NOTE]
> The `{tenancy}` part of the exception is optional, so won't always be present.

## Tenant Missing

Some operations within Sprout require not only a tenant, but that a tenancy has a current tenant.
In situations where this isn't the case, a `Sprout\Exceptions\TenantMissingException` will be thrown with a message like
the following.

```text
There is no current tenant for tenancy [{tenancy}]
```

An example of the error message is as follows.

```text
There is no current tenant for tenancy [tenants]
```

Unlike many of the other exceptions, this one is used in a wide variety of places, so could come from anywhere.

## Tenant Relation

The `Sprout\Exceptions\TenantRelationException` is an exception class used in two possible ways,
but always in reference to the tenant relation within Eloquent.

### Missing Tenant Relation

All [tenant child models](tenant-child-models) require a relation back to their tenant,
and in cases where one cannot be found, or that one found is not valid,
this exception will be thrown with a message like the following.

```text
Cannot find tenant relation for model [{model}]
```

An example of the error message is as follows.

```test
Cannot find tenant relation for mode [Your\Tenant\Child\Model]
```

### Too Many Tenant Relations

If your [tenant child model](tenant-child-models) has more than one tenant relation,
this exception will be thrown with a message like the following.

```text
Expected one tenant relation, found {count} in model [{model}]
```

An example of the error message is as follows.

```text
Expected one tenant relation, found 2 in model [Your\Tenant\Child\Model]
```
