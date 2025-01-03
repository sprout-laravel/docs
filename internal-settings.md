---
section: Core Concepts
title: Internal Settings
position: 9
slug: internal-settings
description:
  Settings are like config, but they're internal to Sprout and only last as long as the current process/request
---

## Introduction

Sprout settings are a collection of values, similar to config, that is used internally by Sprout and its components.
The values are contained within an instance of the class
`Sprout\Support\SettingsRepository` which extends Laravel's default `Illuminate\Config\Repository` class
which powers the config.
These settings function almost identically to config,
except they are added at runtime as needed, and not stored anywhere outside the request.

The purpose of these settings is to provide generic in-memory values that can be used by other parts of Sprout,
without forcing different components to be aware of each other.

## Accessing

The settings can be accessed on the core Sprout class,
the Sprout facade, the sprout helper method, or the dedicated settings helper method.

```php
app(Sprout\Sprout::class)->settings();

Sprout\Facades\Sprout::settings();

Sprout\sprout()->settings();

Sprout\settings();
```

> [!TIP]
> As the settings class extends Laravel's default config repository, you
> can [both in the same way](https://laravel.com/docs/11.x/configuration#accessing-configuration-values).

## Presets

While the settings themselves can be whatever,
there are a number of default settings that Sprout uses internally.
These values can be used if needed,
but it is highly recommended that you do not modify or remove them unless you're absolutely certain of what you're
doing.

### URL Path

This value contains core path components for the applications URL.
It is set by the [path identity resolver](path-identity-resolvers),
and used by both the [session](session-service-override) and [cookie](cookie-service-override) service overrides.
The setting key is `url.path`, which can be easily accessed through the constant `Sprout\Support\Settings::URL_PATH`.
The value can be retrieved using the `getUrlPath()` method,
and set using the `setUrlPath()` methods on the setting repository.

```php
// Getting
settings()->getUrlPath();
settings()->get(Settings::URL_PATH);

// Setting
settings()->setUrlPath('this/that');
settings()->set(Settings::URL_PATH, 'this/that');
```

### URL Domain

This value contains the domain component for the application URL.
It is set by the [subdomain identity resolver](subdomain-identity-resolvers),
and used by both the [session](session-service-override) and [cookie](cookie-service-override) service overrides.
The setting key is `url.domain`, which can be easily accessed through the constant
`Sprout\Support\Settings::URL_DOMAIN`.
The value can be retrieved using the `getUrlDomain()` method,
and set using the `setUrlDomain()` methods on the setting repository.

```php
// Getting
settings()->getUrlDomain();
settings()->get(Settings::URL_DOMAIN);

// Setting
settings()->setUrlDomain('this.that');
settings()->set(Settings::URL_DOMAIN, 'this.that');
```

### No Database Override

This value is a `bool` that's used to signify whether overrides should override database-based drivers and components.
This value contains the domain component for the application URL.
It is set by the [subdomain identity resolver](subdomain-identity-resolvers),
and used by both the [session](session-service-override) and [cookie](cookie-service-override) service overrides.
The setting key is `url.domain`, which can be easily accessed through the constant
`Sprout\Support\Settings::URL_DOMAIN`.
The value can be retrieved using the `getUrlDomain()` method,
and set using the `setUrlDomain()` methods on the setting repository.

```php
// Getting
settings()->getUrlDomain();
settings()->get(Settings::URL_DOMAIN);

// Setting
settings()->setUrlDomain(false);
settings()->set(Settings::URL_DOMAIN, false);
```

### Secure Cookie

This value is al `bool` and is the same as Laravel's `session.secure` config setting, which is used to control the defaults
for cookies as well as session cookies.
It is currently used by both the [session](session-service-override) and [cookie](cookie-service-override) service overrides.
The setting key is `cookie.secure`, which can be easily accessed through the constant
`Sprout\Support\Settings::COOKIE_SECURE`.
The value can be retrieved using the `shouldCookieBeSecure()` method,
and set using the `setCookieSecure()` methods on the setting repository.

```php
// Getting
settings()->shouldCookieBeSecure();
settings()->get(Settings::COOKIE_SECURE);

// Setting
settings()->setCookieSecure(false);
settings()->set(Settings::COOKIE_SECURE, false);
```

### Cookie Same Site

This value is the same as Laravel's `session.same_site` config setting, which is used to control the defaults
for cookies as well as session cookies,
so it should be one of the `string` values `strict`, `lax`, and `none`, or it can be `null`.
It is currently used by both the [session](session-service-override) and [cookie](cookie-service-override) service overrides.
The setting key is `cookie.same_site`, which can be easily accessed through the constant
`Sprout\Support\Settings::COOKIE_SAME_SITE`.
The value can be retrieved using the `getCookieSameSite()` method,
and set using the `setCookieSameSite()` methods on the setting repository.

```php
// Getting
settings()->getCookieSameSite();
settings()->get(Settings::COOKIE_SAME_SITE);

// Setting
settings()->setCookieSameSite('lax');
settings()->set(Settings::COOKIE_SAME_SITE, 'lax');
```
