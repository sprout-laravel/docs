---
section: Packages
title: First-party packages
position: 0
slug: first-party-packages
description:
  First-party packages the function as addons or extensions of Sprout
---

## Introduction

It doesn't make sense for the core of Sprout
to include everything that could be required within a multitenanted application,
because that would make not only huge, but complex.
So instead, subsets of functionality are broken up into external packages.

## Seedling

<img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/seedling-logo-dark-rounded-square@2x.png') }}">

Sprout Seedling is an addon/extension of Sprout that provides the tenant-specific database functionality.
It's a
separate package as it will have a handful of additional supporting functionality only relevant to this.

## Terra

<img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/terra-logo-dark-rounded-square@2x.png') }}">

Sprout Terra is an addon/extension of Sprout that provides domain support for tenants.
Like with Seedling, this is a separate package as it comes with more support functionality and not just
domain identification.

## Bud

<img src="{{ \Illuminate\Support\Facades\Vite::asset('resources/images/bud-logo-dark-rounded-square@2x.png') }}">

Sprout Bud is an addon/extension of Sprout that provides tenant-specific configuration for Laravel's services.
This package comes with a number of different methods for applying and storing the config.
