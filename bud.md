## Introduction

Bud is an addon for Sprout that builds upon the core [service override](service-overrides) functionality, to allow for
tenant-specific configurations.
It allows tenants to have their own;

- Auth Providers
- Broadcasting Connections
- Cache Stores
- Database Connections
- Filesystem Disks
- Logging Channels
- Mailers
- Queue Connections

It works by [encrypting](https://laravel.com/docs/11.x/encryption) the tenants config, and storing it a config store.
Config stores are similar to cache stores, except they store based on the following values:

- Tenancy Name
- Tenant ID
- Service Name
- Type Name (Connection name, cache store name, etc)

It comes out of the box with support for using the filesystem, and the database as a cache store, but is easily 
extendable to support things like the AWS secret manager.

> [!CALLOUT]
> Bud is currently under development, but you can keep track of the development using either the 
> [project board](https://github.com/orgs/sprout-laravel/projects/3/views/2?pane=issue&itemId=85872053&issue=sprout-laravel%7Cbud%7C1)
> or the [GitHub repository](https://github.com/sprout-laravel/bud).
