---
section: Getting Started
title: Terminology
position: 6
---
# Terminology

[[TOC]]

One of the aims of Sprout was to keep things simple, so extra effort has gone into the naming of its components. That being said, some terms used may be partially ambiguous, or not be entirely clear if you don’t already know how it all works, or you aren’t a native English speaker. 

Most of these pieces of terminology are explained in more detail later on in the documentation, but hopefully, what you find below is enough to clear some bits up.

## Tenant
The tenant is whatever makes sense for your application. If you’re building a blogging platform, your tenant may be actually be a “Blog”. If you’re building a team-based SaaS, your tenant is probably a “Team” or “Company”. I would always advise against naming your tenant “Tenant”, as its name should apply to what the application is.

### Tenant Identifier
A tenant identifier is a `string` that is used to identify a tenant for a given HTTP request. They are typically human-readable, and safe for public plain-text usage.

#### Tenant Identification
Tenant identification is the process of using an identifier to resolve a tenant. This typically happens by extracting an identifier from an incoming HTTP request, resolving the tenant, and setting is as the current tenant.

### Tenant Key
A tenant key is a `string`, `int` or other type that can be simplified to one of these, that is used to identify tenants internally. Because of its use, this will typically be the primary key for the tenant model, which is usually an `int` and called `id`.

#### Tenant Loading
Tenant loading is the process of using a key to resolve a tenant. This typically happens through programmatic means using internal components, such as when handling Eloquent relations.

## Tenancy
The tenancy has two similar definitions and usages. The first, is that the tenancy, is a group that contains all the things that belong to a particular tenant. The second is the current state, meaning that if you’re viewing the application and there’s a current tenant, you are within that tenant's tenancy.

## Identity Resolver
Identity resolvers are classes within Sprout that are responsible for finding and extracting an identifier from a request or route.

## Tenant Provider
The tenant provider is a class within Sprout that is responsible for retrieving instances of the tenant using its identifier or key.
