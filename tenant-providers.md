---
section: Core Concepts
title: Tenant Providers
position: 1
slug: tenant-providers
description:
  Every multitenanted application needs a way to retrieve instances of its tenants, regardless of where and how the tenant is stored. This is where the tenant provider comes in.
---

## Introduction

One of Sprouts aims is to support all the different ways that people may build multitenanted applications,
which cannot be achieved without a few abstractions.
Tenant providers are one of these abstractions,
as they abstract out the logic of retrieving instances of your tenant class, without understanding how they're stored.

Tenant providers are based on user providers,
a feature of Laravel's native auth functionality
that abstracts away the logic of retrieving authenticated users from their storage medium.
In fact, the two classes are very, very similar.

## How they work


## Provided Implementations

### Eloquent


### Database

## I need something else
