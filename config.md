# Config
- [Configuring Sprout][1]
- [Sprout Config][2]
	- [Supported Resolution Hooks][3]
	- [Tenancy Bootstrappers][4]
	- [Service Overrides][5]
- [Multitenancy Config][6]
	- [Defaults][7]
	- [Tenancies][8]
		- [Tenancy Provider][9]
		- [Tenancy Options][10]
	- [Tenant Providers][11]
	- [Identity Resolvers][12]

## Configuring Sprout
Sprout comes with two sets of config, one that lets you configure Sprout itself, and one that lets you configure and control the implementation of multitenancy within your Laravel application. 

The decision to use two config files, rather than one, was made to provide a level of separation between the two, rather than have one file where you configure the package and its implementation.

## Sprout Config
The sprout config is published as `sprout.php`, and it contains core configuration options for the package as a whole.

### Supported Resolution Hooks
The first option in the file is called `hooks`, which determines the points during a request’s lifecycle where tenant resolution can happen. This is provided as an array containing cases from the `Sprout\Support\ResolutionHook` enum. The default value is as follows:

```php
'hooks' => [
    // \Sprout\Support\ResolutionHook::Bootstrapping,
    // \Sprout\Support\ResolutionHook::Booting,
    \Sprout\Support\ResolutionHook::Routing,
    \Sprout\Support\ResolutionHook::Middleware,
],    
```

Adding or removing values from here will affect what Sprout registers and attempts to do. For example, without the `Routing` entry, the listener for the `Illuminate\Routing\Events\RouteMatched` event will not be registered.

> [!NOTE]
> Currently, Sprout only supports the routing and middleware hooks, but the other two are there to align with planned work in the future.

### Tenancy Bootstrappers
The second option you’ll encounter is `bootstrappers`, which contains an array of classes that should be used to bootstrap a tenancy. The values in here are fully qualified class names that are in fact listeners for the `Sprout\Events\CurrentTenantChanged` event. The default value is as follows:

```php
'bootstrappers' => [
    // Set the current tenant within the Laravel context
    \Sprout\Listeners\SetCurrentTenantContext::class,
    // Calls the setup method on the current identity resolver
    \Sprout\Listeners\PerformIdentityResolverSetup::class,
    // Performs any clean-up from the previous tenancy
    \Sprout\Listeners\CleanupServiceOverrides::class,
    // Sets up service overrides for the current tenancy
    \Sprout\Listeners\SetupServiceOverrides::class,
],
```

This list exists to give you control over the default things that Sprout tries to do when a tenancy is being bootstrapped, primarily, the order in which they happen. If you have something that you want to happen during the bootstrapping of a tenancy, but it needs to happen before one of the default bootstrappers, but after others, you can add it here.

> [!NOTE]
> While you can add your own event listener here, if it can be called after those contained within this config option, it’s best to just register the listener normally.

### Service Overrides
The third and final option in the sprout config file is `services`, which contains an array of classes that each override a service within the Laravel application. The values in here must be fully qualified class names that implement the `Sprout\Contracts\ServiceOverride` interface. The default value is as follows:

```php
'services' => [
    \Sprout\Overrides\StorageOverride::class,
    \Sprout\Overrides\JobOverride::class,
    \Sprout\Overrides\CacheOverride::class,
    \Sprout\Overrides\AuthOverride::class,
    \Sprout\Overrides\CookieOverride::class,
    \Sprout\Overrides\SessionOverride::class,
],
```

You can add, remove and rearrange this list dependent on the requirements of your application. 

> [!INFO]
> You can read more about service overrides here.

## Multitenancy Config
The multitenancy config is published as `multitenancy.php`, and if you’ve ever spent any time with Laravel’s `auth.php` config, you should recognise this. This file is where you configure the details of your multitenancy implementation.

### Defaults
The first option in the multitenancy config is `defaults`, which, much like `auth.defaults`, contains default options to use, allowing you to avoid providing the tenancy, provider, or resolver name every time you need to deal with it. The default value is as follows:

```php
'defaults' => [
    'tenancy'  => 'tenants',
    'provider' => 'tenants',
    'resolver' => 'subdomain',
],
```

There are three child options within this.

- `tenancy` — This contains the name of a tenancy configured in `multitenancy.tenancies`.
- `provider` — This contains the name of a tenant provider configured in `multitenancy.provider`.
- `resolver` — This contains the name of an identity resolver configured in `multitenancy.resolvers`.

### Tenancies
The next option is `tenancies`, which is Sprouts version of `auth.guards`. Sprout supports multiple tenancies, meaning, you can have an application that contains two kinds of distinct tenants, though in almost everyone’s case, a single tenancy type will do. 

> [!INFO]
> You can read more about tenancies here.

All tenancies have a name, which is used as the array key within this section, as well as the value for the default tenancy. The default value is as follows:

```php
'tenancies' => [

    'tenants' => [
        'provider' => 'tenants',
        'options'  => [
            TenancyOptions::hydrateTenantRelation(),
            TenancyOptions::throwIfNotRelated(),
        ],
    ],
],
```

#### Tenancy Provider
Within an individual tenancy config, you can also provide the `provider` option, which should correspond to the name of a tenant provider defined in `multitenancy.providers`. If you don’t add this option, or simply set it to `null`, the previously supplied default tenant provider will be used.

#### Tenancy Options
When configuring a tenancy you can also provide the `options` option, which should be an array of values provided by `Sprout\Contracts\Tenancy\TenancyOptions`. These options will define the behaviour of various elements of Sprout for a given tenancy.

> [!INFO]
> You can read more about tenancy options here.

### Tenant Providers
Next we have the `providers` option, which is used to configure the tenant providers for Sprout, similar to `auth.providers`. Tenant providers are responsible for retrieving your configured instances of the `Sprout\Contracts\Tenant` interface, by their identifier or key.

> [!INFO]
> You can read more about tenant providers here.

All tenant providers have a name, which is used as the array key within this section, as well as the value for the default provider, and a tenancy’s provider. The default value is as follows:

```php
'providers' => [
    'tenants' => [
        'driver' => 'eloquent',
        'model'  => \App\Tenant::class,
    ],
    'backup' => [
        'driver' => 'database',
        'table'  => \App\Tenant::class,
    ],
],
```

When configuring a tenant provider, you must provide a `driver`, and the specific driver used will decide which other options are required. 

> [!INFO]
> You can read more about tenant provider drivers here.

### Identity Resolvers
The final option in here is `resolvers`, which doesn’t actually have a cousin in the auth config. Identity resolvers are abstracted logic responsible for retrieving a tenant's identity from a request or route.

> [!INFO]
> You can read more about identity resolvers here.

Much like with tenancies and tenant providers, all identity resolvers have a name, which is used as the array key within this section, as well as the value for the default resolver.

```php
'resolvers' => [
    'subdomain' => [
        'driver'  => 'subdomain',
        'domain'  => env('TENANTED_DOMAIN'),
        'pattern' => '.*',
    ],
    'path' => [
        'driver'  => 'path',
        'segment' => 1,
    ],
    'header' => [
        'driver' => 'header',
    ],
    'cookie' => [
        'driver' => 'cookie',
    ],
    'session' => [
        'driver'  => 'session',
        'session' => 'multitenancy.{tenancy}',
    ],
],
```

Again, similar to when configuring a tenant provider, identity resolvers must have a `driver`, and the specific driver used will decide which other options are required.

> [!INFO]
> You can read more about identity resolver drivers here.

[1]:	#configuring-sprout
[2]:	#sprout-config
[3]:	#supported-resolution-hooks
[4]:	#tenancy-bootstrappers
[5]:	#service-overrides
[6]:	#multitenancy-config
[7]:	#defaults
[8]:	#tenancies
[9]:	#tenancy-provider
[10]:	#tenancy-options
[11]:	#tenant-providers
[12]:	#identity-resolvers
