---
description: Tenant resolution is a big part of multitenancy, as it's the part that handles the identification
  and loading of the current tenant. Sprout comes with a number of different options for how to go about this.
---

## Introduction

Tenant resolution is the process of determining the current tenant, and then resolving it before setting it as the
actual current tenant.
Within Sprout, there are two paths for tenant resolution, identifying, and loading.

## Tenant Identification

Tenant identification is a process that uses the [tenant identifier](tenants#the-tenant-identifier) to identify the
tenant.
This is the automated process that makes sure that the correct tenant is available when processing your routes.
To achieve this, an [identity resolver](#) is used, which is a class that is responsible for extracting a tenants'
identifier from an incoming HTTP request.
These resolvers are configured using the [`multitenancy.resolvers`](configuration#identity-resolvers) configuration
option.
These classes also know how to configure a route to make the best use of themselves.

### Tenant Routes

Your multitenanted application will have routes that require a current tenant, which is what we refer to as tenant
routes, because they belong to the tenant.
There are two ways to define tenant routes, but both allow you to set two optional parameters, which follow the same
rules as [middleware parameters](https://laravel.com/docs/11.x/middleware#middleware-parameters).

- The resolver — The name of a resolver registered in the
  [`multitenancy.resolvers`](configuration#identity-resolvers) config.
- The tenancy — The name of a tenancy registered in the
  [`multitenancy.tenancies`](configuration#tenancies) config.

#### Automatic Routes

The best way to define tenant routes is using
the [router macro](https://github.com/sprout-laravel/sprout/blob/1.x/src/Http/RouterMethods.php#L34) that's used in the
[installation guide](installation#registering-tenant-routes).
This is the preferred way to define your routes, as some of the identity resolvers are capable of making use of
[route parameters](#parameter-based-identity-resolvers), and this approach will make sure those parameters are defined
on the routes.
Without these parameters, there can be a number of issues and side effects that can make creating URLs and serving
non-tenanted routes difficult, amongst other things.

```php
// Default
Route::tenanted(function () {
    // Tenant Routes
});

// Default tenancy, manual resolver
Route::tenanted(function () {
    // Tenant Routes
}, 'subdomain');

// Default resolver, manual tenancy
Route::tenanted(function () {
    // Tenant Routes
}, null, 'tenants');

// Manual resolver, manual tenancy
Route::tenanted(function () {
    // Tenant Routes
}, 'subdomain', 'tenants');
````

> [!NOTE]
> You can wrap your usage of this macro in as many route groups as you want, though some options will be overwritten
> by some identity resolvers.

#### Manual Routes

Sometimes you need to manually specify a route as being tenanted, and in those cases all you need is the `sprout.
tenanted` middleware, which is an alias for `Sprout\Http\Middleware\SproutTenantContextMiddleware`.
You can use either the alias, or the full middleware class name, but as mentioned above, manually providing the
tenant route middleware can have knock-on side effects, particularly with resolvers that use route parameters.

```php
// Default
Route::middleware(['sprout.tenanted'])->get('/', '');

// Default tenancy, manual resolver
Route::middleware(['sprout.tenanted:subdomain'])->get('/', '');

// Default resolver, manual tenancy
Route::middleware(['sprout.tenanted:,tenants'])->get('/', '');

// Manual resolver, manual tenancy
Route::middleware(['sprout.tenanted:subdomain,tenants'])->get('/', '');
```

#### Generating URLs

When defining your tenant routes, it is recommended that you use
[named routes](https://laravel.com/docs/11.x/routing#named-routes)
so that [generating URLs for them](https://laravel.com/docs/11.x/urls#urls-for-named-routes) is easy.
If you're using a [parameter-based identity resolver](#parameter-based-identity-resolvers), and you have a current
tenant that has been identified using that resolver, the parameters will be automatically populated, so you can use
`route()` without needing to specify it.

If you are generating the URL from somewhere outside the multitenanted context, as in, there's no tenant, or for a route
that uses a different identity provider; the normal approach won't work.
Rather than providing the parameter yourself, however, there's a Sprout method just for this, that can be accessed
using the `Sprout` facade, or the `sprout()` helper method.

```php
Sprout\Facades\Sprout::route('my.route.name', $tenant);

sprout()->route('my.route.name', $tenant);
```

This method has the same arguments as Laravels `route()` helper, but with a couple of extras to deal with possible
parameters.
You can see the method and its signature
[here](https://github.com/sprout-laravel/sprout/blob/1.x/src/Sprout.php#L323), and this is a brief breakdown of
those available.

- `name` - The name of the route. Should be a `string` and is required.
- `tenant` - The tenant you're generating the URL for.
  Should be an instance of the `Sprout\Contracts\Tenant` interface, and is required.
- `resolver` - The registered name of the resolver to use.
  Can be a `string`, or `null`, optional and defaults to `null`.
- `tenancy` - The registered name of the tenancy to use. Can be `string`, or `null`, optional and defaults to `null`.
- `parameters` - An array of parameter values to use when generating the URL. Optional and shouldn't include the
  tenant parameters.
- `absolute` - Whether to generate an absolute URL (including the domain). Should be a `bool`, and is optional and
  will default to `true`.

> [!NOTE]
> If no name is provided for `tenancy`, the default will be used.
> If there's no name provided for `resolver`, it will try and use the resolver used to identify the current tenant, if
> available, and if not, it'll use the default one.

### Resolution Hooks

Sprout supports two different places within a requests' lifecycle where tenant identification can occur, with provisions
for a third in the future.
These two places are both within the routing part of the applications' lifecycle, and you can enable or disable either
using the [`sprout.core.hooks`](configuration#resolution-hooks) config option.

The first is at the beginning of the routing phase, right after a route is matched.
If the identification happens here, the tenant will be available for dependency injection within the entire middleware
stack, in case you have some that requires a tenant.

The second is during the running of the middleware, which will make the tenant available for the controller/route
handler, but not for other middleware constructors.
If you want the tenant to be available when other pieces of middleware are run, you'll need to make sure that the
identification middleware has a [higher priority](https://laravel.com/docs/11.x/middleware#sorting-middleware)
than the others.

### Parameter-based Identity Resolvers

Parameter-based identity resolvers are identity resolvers that **can** make use of Laravel's
[route parameters functionality](https://laravel.com/docs/11.x/routing#route-parameters).
These resolvers are all capable of handling routes that don't have the tenant parameters, but when using the
[automatic routes](#automatic-routes) approach, they'll make sure the route is configured with them.

All first-party identity resolvers provided by Sprout, whether in the core package or an addon, that work with route
parameters allow you to control the name of the parameter, and the pattern used to match it.

#### Parameter Naming

By default, all route parameters are named using the registered name of the tenancy from
[`multitenancy.tenancies`](configuration#tenancies), and the registered name of the resolver from
[`multitenancy.resolvers`](configuration#identity-resolvers).
Since the default tenancy is called `tenants`, and the default resolver is called `subdomain`, the route parameter
would be named `tenants_subdomain`.

To change this, all you need to do is provide the `parameter` config option when defining the resolver.
If you don't provide this value, or leave it empty, the value `{tenancy}_{resolver}` will be used.
The `{tenancy}` and `{resolver}` parts are placeholders, which are replaced when the route is defined.
Each of these placeholders has three different forms for different cases.

- `{tenancy}`/`{resolver}` — All lowercase, e.g. `tenants` or `subdomain`
- `{Tenancy}`/`{Resolver}` — First letter uppercased, e.g. `Tenants` or `Subdomain`
- `{TENANCY}`/`{RESOLVER}` — All uppercase, e.g. `TENANTS` or `SUBDOMAIN`

```php
'subdomain' => [
    'driver'    => 'subdomain',
    'domain'    => env('TENANTED_DOMAIN'),
    'parameter' => '{tenancy}_{resolver}'
],
```

> [!NOTE]
> Some of the identity resolvers that don't make use of route parameters still make use of the same functionality,
> allowing you to provide a name with dynamic values through the placeholders.

#### Parameter Pattern

Laravel's routing allows you to provide a
[regular expression constraint](https://laravel.com/docs/11.x/routing#parameters-regular-expression-constraints)
for a route parameter, which Sprout will let you do using the `pattern` option when configuring the resolver.
The value you provide must be a valid constraint as defined within Laravel.
If you wish to use the default pattern, you can set this value to `null`, to remove the option from the config entirely.

```php
'subdomain' => [
    'driver'  => 'subdomain',
    'domain'  => env('TENANTED_DOMAIN'),
    'pattern' => '.*'
],
```

### Available Identity Resolvers

Out-of-the-box Sprout comes with five easy to use identity resolvers, each of which covers one of the common approaches
to multitenancy.
While every step has been taken to make these resolvers easy to use, and easy to implement, some of them may have
restrictions and limitations for one of two reasons.

- The method or concepts they use are simply not compatible with some others because of how the web works.
- It is limited by the functionality and features provided by Laravel.

There are few restrictions and limitations within Sprout in general, and in some cases I've gone as far as to PR changes
to Laravel to make things easier, but there is still a handful that cannot be avoided.
Any of the identity resolvers that have these restrictions or limitations will state them in their section below.

#### Subdomain

The subdomain identity resolver allows you to provide your tenant identifier as the subdomain component of the requests'
hostname, which is one of the most recognisable forms of multitenancy.
Sprout comes with this resolver already configured, but if you wish to configure your own, you need to set the driver to
`subdomain`, and provide a domain using the `domain` option.
The domain is used to construct the route, making sure that the route is only accessible from subdomains of that domain.
This is also a [parameter-based identity resolver](#parameter-based-identity-resolvers),
so you can use the `pattern` and `parameter` options too.

```php
'subdomain' => [
    'driver'    => 'subdomain',
    'domain'    => env('TENANTED_DOMAIN'),
    'parameter' => '{tenancy}_{resolver}',
    'pattern'   => '.*'
],
```

Because of how this identity resolver works, when defining your tenant routes using the
[automatic approach](#automatic-routes), if you define the routes in a group that has a domain provided, it will be
overwritten by the resolver.

> [!TIP]
> When using the subdomain resolver with an application that has non-tenanted routes, it is very **highly recommended**
> that you wrap your non-tenanted routes in a
> [route group that specifies the domain](https://laravel.com/docs/11.x/routing#route-group-subdomain-routing).
> If you don't do this, all the non-tenanted routes will be accessible under the tenants' subdomain, without
> identification taking place.

#### Header

The header identity resolver allows you to provide your tenant identifier as a header within the request, which
would typically be used in something like an API.
Sprout comes with this resolver already configured, but if you wish to configure your own, you need to set the driver to
`header`.
You can also customise the header name using the `header` option, which, although the resolver isn't
parameter-based, it uses the same functionality for its naming.
The default value for `header` is `{Tenancy}-Identifier`.

```php
'header' => [
    'driver' => 'header',
    'header' => '{Tenancy}-Identifier',
],
```

When defining tenant routes that use this resolver, there are no restrictions on the routing options you can use.
The only thing to note is that this resolver is not suitable for routes that need to be accessed in a browser, as you
cannot generate a link that includes a header.

> [!TIP]
> All responses from requests that make use of this identity resolver will have the header attached, allowing you to
> see the identifier for the tenant that handled the request.

#### Path

The path identity resolver allows you to provide your tenant identifier as a path segment within the request URI.
Sprout comes with this resolver already configured, but if you wish to configure your own, you need to set the driver to
`path`.
If you're using the [manual approach](#manual-routes) to tenant routes, you should provide the `segment` config option,
if the identifier appears anywhere, but the first path segment, as this defaults to `1`.
This is also a [parameter-based identity resolver](#parameter-based-identity-resolvers),
so you can use the `pattern` and `parameter` options too.

```php
'path' => [
    'driver'    => 'path',
    'segment'   => 1,
    'parameter' => '{tenancy}_{resolver}',
    'pattern'   => '.*'
],
```

> [!NOTE]
> The `segment` option has no effect if you're only ever using the [automatic approach](#automatic-routes) to
> tenant routes, as it'll use parameters and inherit any path prefixes.

#### Cookie

The cookie identity resolver allows you to provide the tenant identifier in a cookie that's present on the request.
Sprout comes with the resolver already configured, but if you wish to configure your own, you need to set the driver to
`cookie`.
You can also control the name of the cookie using the `cookie` config option, which defaults to `{Tenancy}-Identifier`,
and allows for the same placeholder approach as parameters.

Because this identity resolver deals with cookies, which are a form of state, it will also set the tenant cookie
once a tenant becomes the current one.
This ensures that the cookie is passed in the response, but also that any existing cookies are updated.
If you want to control the options used to set this cookie, you can provide `options` config option,
which should be an array, and can contain `minutes`, `path`, `domain`, `secure`, `http_only` and `same_site` entries,
the same mentioned in [Laravel's documentation](https://laravel.com/docs/11.x/responses#attaching-cookies-to-responses).

> [!NOTE]
> All but the `minutes` option are overrides for the values of the same name in the session config, which
> will be used by default.
> These values are all **optional**.

```php
'cookie' => [
    'driver'  => 'cookie',
    'cookie'  => '{Tenancy}-Identifier',
    'options' => [
        'minutes'   => 43800,
        'path'      => '/tenants/',
        'domain'    => 'localhost',
        'secure'    => false,
        'http_only' => true,
        'same_site' => 'lax'
    ],
],
```

> [!WARNING]
> Because of how Laravel handles cookies, it is not possible to use this identity
> resolver, and the [cookie service override](#).
> If using this resolver, with the service override enabled, an exception will be thrown during Laravel's boot phase.

#### Session

The session identity resolver allows you to identify tenants using a value stored within the session.
Sprout comes with the resolver already configured, but if you wish to configure your own, you need to set the driver
to `session`.
You can also set the session key used, using the `session` config option, which allows for the same placeholder usage
as parameters, and can also use dot notation.
The default for this is `multitenancy.{tenancy}`.

```php
'session' => [
    'driver'  => 'session',
    'session' => 'multitenancy.{tenancy}'
]
```

> [!WARNING]
> Because of how Laravel handles sessions, it is not possible to use this identity
> resolver, and the [session service override](#).
> If using this resolver, with the service override enabled, an exception will be thrown during Laravel's boot phase.

##### Middleware Only

This is the only core identity resolver that cannot make use of all possible places where tenant identification can
take place.
Because sessions are not present on the request until the `StartSession` middleware has been run, it must happen during
the middleware phase.
While not strictly necessary, it's recommended that you set the Sprout middleware to come after `StartSession` in
middleware priority, which be done in the `bootstrap/app.php` files.

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToPriorityList(
            Illuminate\Session\Middleware\StartSession::class,
            Sprout\Http\Middleware\SproutTenantContextMiddleware::class
        );
    });
```

> [!WARNING]
> It is extremely important that you **only do this** if you're using the session resolver, otherwise this will cause
> issues with the [session service override](#), as well as other parts of both Laravel and Sprout.

## Tenant Loading

Tenant loading is the other side of [tenant identification](#tenant-identification), and makes use of the 
[tenant key](tenants#the-tenant-key) rather than the identifier.
It's also far, far simpler than identification, as it only happens internally and automatically.
There are no specific features, drivers or settings that are needed to make use of this; it'll just work.
