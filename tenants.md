## Introduction

Tenants are a core part of any multitenancy application;
therefore, it probably comes as no surprise to you
that the core of a lot of Sprouts functionality is built around the concept of tenants too.

## What are Tenants?

This is a tricky question to answer, as it can mean different things depending on the context.
It also depends on whether you mean tenants as a theoretical concept or tenants within Sprout.
So let's take a quick look at both.

### As a Concept

Tenants as a concept are entities within your application that sit at the top of the hierarchy, or near the top,
forming the head of a group of related entities.
I appreciate that's a bit of a mouthful, so let's break it down a bit.
Take the `Blog` example from the [installation guide](installation).

When you access a blog, the entity that would sit at the top of all you see, would be `Blog`.
Every `Post` you see will belong to the `Blog`, and every `Category` or `Tag` assigned to a `Post`, would also
belong to a `Blog`.
So, for an application that's a blogging platform, the `Blog` would be the tenant.

What the tenant in your application will be, will depend entirely on what you're building, and how you want to build it.
Unfortunately, that's not something I can tell you or create an automated process to determine.

### Within Sprout

Tenants within Sprout are a lot simpler than that.
They are classes that implement the `Sprout\Contracts\Tenant` interface, whether that's a model, or a custom class.
