---
section: Service Overrides
menu: Jobs
title: Job Service Override
position: 1
slug: jobs-service-override
description:
  The job service override allows jobs to function as if they are being run within a tenants' tenancy, despite being run externally
---

## Introduction

The job service override is an implementation of Sprouts [service override](service-overrides) functionality,
that allows jobs to be processed within a tenants' tenancy.

If your job relies
on [tenant child models](tenant-child-models),
specifically within the constructor, those models will
be [serialised](https://laravel.com/docs/11.x/queues#class-structure)
and loaded fresh from the database when the job is run.
Ordinarily this would cause several possible exceptions to be thrown,
as the [tenant child model](tenant-child-models) functionality would kick in
and complain that there's no current tenancy, let alone a current tenant.

Similar issues would occur if your job creates new child models without a current tenant,
unless, of course, you're passing the tenant to the job too and manually setting that relation,
which would be less than ideal.

This service override addresses both of these issues
by setting not only the [multitenanted context](multitenanted-context), but any [tenancies](tenancies) and their
[tenants](tenants) that were active when the job was dispatched.

## How it works

This service override relies on the presence of the [
`Sprout\Listeners\SetCurrentTenantContext` tenancy bootstrapper](configuration#setcurrenttenantcontext),
which adds the current tenant to Laravel's [context functionality](https://laravel.com/docs/11.x/context).
When a job is dispatched to the queue,
Laravel ["dehydrates" the context](https://laravel.com/docs/11.x/context#dehydrating) and appends it to the job,
allowing the job to inherit the context that was present when it was dispatched.

Before the queue processes a job,
Laravel dispatches a `Illuminate\Queue\Events\JobProcessing` event, which this service override listens for.
By the time this event is dispatched,
the context has been ["rehydrated"](https://laravel.com/docs/11.x/context#hydrated),
allowing this service override to load any tenancies and their tenants.

> [!NOTE]
> The context stores the [tenants' key](tenants#tenant-keys),
> so when the current tenant is set, it is [loaded using its key](tenancies#setting-the-tenant-by-key).
> This process will bootstrap the tenancy,
> meaning that the job will have access to not only the current tenant,
> but any service overrides or other functionality that is enabled during that process.

## Using

The only thing you need to do to use this service override, is to enable it in
the [services section](configuration#services) of
the [sprout config](configuration#sprout-config),
and dispatch a job while there's a current tenant.

```php
'services' => [
    \Sprout\Overrides\JobOverride::class,
],
```

## Considerations

This service override is **intentionally** named the _job service override_,
and not the _queue service override_, as that would be a very different set of functionality.
This override deals only with individual jobs, not the queue as a whole.
