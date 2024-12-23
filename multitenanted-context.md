---
section: Core Concepts
title: Multitenanted Context
position: 4
slug: multitenanted-context
description:
  In your application you'll have code that runs both inside and outside the multitenanted context. When outside, you don't want multitenanted code running, right?
---

## Introduction

Within Sprout, there's a lot of functionality that requires there to be a current [tenant](tenants),
or at the very least, a current [tenancy](tenancies).
This code is often hooked up to be called automatically in particular situations.
These situations, however, are not unique to the multitenanted part of your application,
and will most likely occur within the parts that do not have a tenant or tenancy.

This presents an interesting problem
because you want the multitenanted code to error if there isn't a tenancy or tenant,
but only when dealing with multitenancy.
This is where the "multitenanted context" comes in.

## How it works

This whole subset of functionality is remarkably simple,
and comes down to three methods on the core `Sprout\Sprout` class, which either read or write a single boolean.

```php
public function markAsInContext(): self;

public function markAsOutsideContext(): self;

public function withinContext(): bool;
```

Because the `Sprout\Sprout` class is registered as a singleton with Laravel's container,
and is instantiated during the application boot process (inside Sprouts' service provider),
the class can be used to keep track of "state".

There's only a single place in the entirety of Sprout that sets the current multitenanted context,
and it's when [the current tenancy is set](tenancies#using-the-sprout-core).
During this process the application is marked
as being within multitenanted context using the `markAsInContext()` method.
If you wish to check the context within your own code,
you can use `withinContext()` through the core `Sprout\Sprout` class, [the helper](helpers#sprout)
or [the facade](facades#sprout).

```php
// From the Sprout core class
app(Sprout\Sprout::class)->withinContext();

// From the Sprout helper
sprout()->withinContext();

// From the facade
Sprout\Facades\Sprout::withinContext()
```

> [!WARNING]
> The `markAsOutsideContext()` and `markAsInContext()` methods should only be used
> if you fully understand what you're doing, and are prepared to deal with the possible side effects.
