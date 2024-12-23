---
section: Core Concepts
title: Lifecycle
position: 6
slug: lifecycle
hidden: true
description:
  Sprout does certain things at certain points during Laravel's lifecycle, but it also has its own lifecycle and functionality that allows you to work with it.
---

> [!NOTE]
> This section of the documentation is going to be one of the last pieces to be completed,
> as it directly reflects how the package works, which is subject to change.

## Laravel Lifecycle

The first lifecycle we need to look at is Laravel's, so you can see what Sprout is doing at the relevant points.

### Service Provider Registration

The service provider registration stage is the first part of Laravel's lifecycle where Sprout does anything,
and it does the following, in the following order.

- Registers bindings for the core Sprout class
    - Registers `Sprout\Sprout` as a singleton
    - Aliases `Sprout\Sprout` to `sprout`
    - Binds `Sprout\Support\SettingsRepository` using the `Sprout\Sprout::settings()` method
- Registers bindings for the managers
    - Registers `Sprout\Managers\ProviderManager` as a singleton
    - Registers `Sprout\Managers\IdentityResolverManager` as a singleton
    - Registers `Sprout\Managers\TenancyManager` as a singleton
    - Aliases `Sprout\Managers\ProviderManager` as `sprout.providers`
    - Aliases `Sprout\Managers\IdentityResolverManager` as `sprout.resolvers`
    - Aliases `Sprout\Managers\TenancyManager` as `sprout.tenancies`
- Aliases the `Sprout\Http\Middleware\TenantRoutes` middleware as `sprout.tenanted` with the router
- Adds the mixin `Sprout\Http\RouterMethods` to `Illuminate\Routing\Router`
- Registers a call to `Sprout\Sprout::bootOverrides()` to be called once the application has finished booting

### Service Provider Booting

During the booting stage of Laravel's lifecycle, Sprout does the following, in the following order.

- Registers publishable config
    - Registers the `sprout.php` config file for publishing
    - Registers the `multitenancy.php` config file for publishing
    - Registers both config files with the tags `config` and `sprout-config`
- Registers the [configured service overrides](configuration#services)
- Registers event listeners
    - Registers the `Sprout\Listeners\IdentifyTenantOnRouting` listener to the `Illuminate\Routing\Events\RouteMatched`
      event for the [routing resolution hook](resolution-hooks#the-routing-hook)
- Registers the [configured tenancy bootstrappers](configuration#bootstrappers)
    - Registers each bootstrapper as a listener for the `Sprout\Events\CurrentTenantChanged` event

### After Booting

Once Laravel has fully booted, Sprout does the following, in the following order.

- Boots previously registered [bootable overrides](service-overrides#bootable-service-overrides), in the
  order [they're configured](configuration#services)
    - This will include the following services if they're registered
        - `Sprout\Overrides\AuthOverride`
        - `Sprout\Overrides\JobOverride`
    - This will also include the following services if they're registered, and the services they override have been
      resolved
        - `Sprout\Overrides\CacheOverride`
        - `Sprout\Overrides\SessionOverride`
        - `Sprout\Overrides\StorageOverride`
- This is where the [booting resolution hook](resolution-hooks#the-booting-hook) is ideally going to be

### Routing

During the routing stage of Laravel's lifecycle, Sprout does the following, in the following order.

- Attempts to resolve a tenant when a route is matched,
  if [the routing resolution hook](resolution-hooks#the-routing-hook) is enabled
- Attempts to resolve a tenant during the middleware run,
  if [the middleware resolution hook](resolution-hooks#the-middleware-hook) is enabled

## Sprout Lifecycle

Sprout has its own lifecycle, or rather, a handful of lifecycles for each of the different things that it needs to do.
These lifecycles are as follows.

### Service Override Lifecycle

Service overrides have their own lifecycle that's not entirely unlike that of a service provider.
The following is a breakdown of this lifecycle into its steps.

#### Registering a Service Override

When a service override is registered, the following happens in the following order.

- The [service override](service-overrides) class is validated as being a subclass of `Sprout\Contracts\ServiceOverride`
    - If the class is invalid, an `InvalidArgumentException` is thrown
- The service override class is marked as registered
- A `Sprout\Events\ServiceOverrideRegistered` event is dispatched with the service override class
- If the service override is [deferrable](service-overrides#deferrable-service-overrides), it goes through
  the [deferrable process](#deferring-a-service-override)
- If the service override is not deferrable, it goes through
  [processing](#processing-a-service-override)

#### Processing a Service Override

Once a service override is registered, it needs to be processed.
During this processing, the following happens, in the following order.

- A `Sprout\Events\ServiceOverrideProcessing` event is dispatched with the service override class
- The service override class is resolved using Laravels container, and the resulting instance is stored
- If the service override is [bootable](service-overrides#bootable-service-overrides), it goes through
  the [bootable process](#booting-a-service-override)
- A `Sprout\Events\ServiceOverrideProcessed` event is dispatched with the service override instance

#### Deferring a Service Override

If a registered service override is [deferrable](service-overrides#deferrable-service-overrides),
the following happens, in the following order.

- Its [processing](#processing-a-service-override) is delayed until its service has been resolved within Laravels
  container
- If the service it watches has already been resolved, [processing happens immediately](#processing-a-service-override)

#### Booting a Service Override

If a registered service override is [bootable](service-overrides#bootable-service-overrides),
the following happens in the following order.

- It is registered as being bootable
- If the service overrides have already been booted, it is booted

Once a registered and bootable service override is actually booted, the following happens in the following order.

- The [`ServiceOverride::boot()` method](service-overrides#bootable-service-overrides) is called
- The service override is flagged as having been booted
- A `Sprout\Events\ServiceOverrideBooted` event is dispatched with the service override instance

### Tenant Resolution Lifecycle
