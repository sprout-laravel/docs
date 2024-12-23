---
section: Core Concepts
title: Resolution Hooks
position: 5
slug: resolution-hooks
description: 
  There are certain places within the lifecycle of a Laravel application that it makes sense to resolve a tenant. Resolution hooks are a simple way to configure this.
---

## Introduction

Resolution hooks are a simple way for Sprout to enable/configure certain pieces of functionality
that support the resolution of tenants during certain parts of Laravel's lifecycle.
These hooks are handled by the enum `Sprout\Support\ResolutionHook`,
and whether they're enabled or not is controlled by the [`hooks` part of the sprout config](configuration#enabled-hooks).

## The Routing hook

The `Sprout\Support\ResolutionHook::Routing` case represents the "routing" hook.
It is also the default hook that all identity resolvers are configured with if none are provided.
If this hook is enabled,
a listener (`Sprout\Listeners\IdentifyTenantOnRouting`)
for the `Illuminate\Routing\Events\RouteMatched` event is registered,
and resolution is attempted when a matching route is found, before middleware runs.

## The Middleware hook

The `Sprout\Support\ResolutionHook::Middleware` case represents the "middleware" hook.
All tenanted routes registered using the appropriate functionality have a piece of middleware
(`Sprout\Http\Middleware\TenantRoutes`)
added to them that serves as a marker for [the routing hook](#the-routing-hook).
If, once the middleware is run, there is no current tenant and this hook is enabled,
the middleware itself will attempt to resolve a tenant.

> [!NOTE]
> The [session identity resolver](session-identity-resolvers) will only work for this hook,
> as the session won't have been initialised otherwise.

## The Booting hook

The `Sprout\Support\ResolutionHook::Booting` case represents the "booting"
hook which only exists to support future functionality.
The idea is that this hook will be used during the booting phase of the framework,
ideally after all the service providers have booted.
