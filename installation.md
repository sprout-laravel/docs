---
description: Install Sprout into your Laravel application and get started on your multitenancy journey.
---

## Meet Sprout

Sprout is an easy to use multitenancy solution that integrates seamlessly into your Laravel application, whether it's a
pre-existing project, or a brand new one.
It's designed to be simple to use, and easy to understand, while still
providing the flexibility you need to build your application the way you want.

Multitenancy can be a complex problem to solve, with many different approaches.
Sprout aims to support as many of these approaches as possible, in a way that takes as much of the complexity out of
your hands as possible.
It does this by coming with a set of defaults that you can use out of the box, but also allows you to customize and
extend it to fit your needs.

## Making Laravel Multitenanted

To make Laravel multitenanted, you'll first need a Laravel application, or at the very least, a fresh install.
If you don't have either of these, check out
the [Laravel documentation](https://laravel.com/docs/11.x/installation#creating-a-laravel-project), and then come back.

### Installing Sprout

Once you have your Laravel application, you'll need to install Sprout, which can be done using composer.

```shell
composer require sprout/sprout
```

Sprout is configured to make use of Laravel's package auto-discovery, so its service provider should be automatically
registered.
If you have this disabled, it's not working for some reason, or you'd like to manually control where Sprout is loaded,
you can manually register it in `bootstrap/app.php`, before your `AppServiceProvider`.

```php
return [
    Sprout\SproutServiceProvider::class,
    App\Providers\AppServiceProvider::class,
];
```

Sprout also comes with a handful of configuration files, which will need to be published using the following command.

```shell
php artisan vendor:publish --provider="Sprout\SproutServiceProvider"
```

### Creating your Tenant

Now that you have Sprout installed, you can get started by creating your applications Tenant.
Sprout supports using [Eloquent](#), or the [database](#) out of the
box, with the ability to [extend](#) it to support other methods.
However, for this example, we'll assume that you're using Eloquent.

To create your tenant, you'll need a model, whether that's one that already exists, or a new one.
The model can be called whatever you like,
but I would recommend a name that makes sense for your application, such as `Company`, `Organization`, or `Team`.
For this example, we'll be using `Blog`.

The model will need a [primary key](https://laravel.com/docs/11.x/eloquent#primary-keys),
and an attribute that contains
the tenant identifier, which we'll assume is called `identifier`.
Once it has these, all you need to do is add the `Tenant` interface and the `IsTenant` trait to the model.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sprout\Contracts\Tenant;
use Sprout\Tenancy\IsTenant;

class Blog extends Model implements Tenant
{
    use IsTenant;
}
```

> [!NOTE]
> If you want to use a different attribute for the tenant identifier, you can override the `getTenantIdentifierName()`
> method.
> [Read more about it here](#).

With the model setup and configured as a tenant, you'll want to open up `config/multitenancy.php` and configure your
tenancy to use the new tenant model.
This config file provides a number of options, but comes with sensible defaults that should work for most applications.
All you need to do right now is change the model in the `tenants` provider, to use your new model.

```php
'providers' => [
    'tenants' => [
        'driver' => 'eloquent',
        'model'  => \Sprout\Database\Eloquent\Tenant::class, // [tl! --]
        'model'  => \App\Models\Blog::class, // [tl! ++]
    ],
],
```

### Registering Tenant Routes

The Next thing to do is register your routes that should be multitenanted.
Before you can do that, you'll need to decide how your tenant will be identified.
For this example, we'll be using [subdomains](#) which Sprout is configured to use by default,
but it also supports using [paths](#), [headers](#), [the session](#) and [cookies](#).

To use subdomains, you'll need to first tell the application what the main domain is, which can be done using the
`TENANTED_DOMAIN` environment variable.

```env
TENANTED_DOMAIN=localhost
```

Finally, you can open your `routes/web.php` file and add your tenant routes using the Sprout route macro.

```php
Route::tenanted(function () {
    Route::get('/', function () {
        return view('welcome');
    });
})
```

> [!TIP]
> If you're using the `subdomain` resolver, and you intend to have routes within your application that are not
> multitenanted, you'll want to wrap those routes in a route group that uses the main applications domain.
> If you don't do this, those routes will also be available under tenants subdomains, which can cause issues.
> [Read more about it here](#).

### Overriding Laravel

Sprout comes with a number of [service overrides](#) by default, all of which are registered in
`config/sprout/overrides.php`.
The default tenancy configuration is configured to use all the overrides, which [can be changed](#), but is
fine for now.

One of these overrides is the [session](#) override, which makes all sessions specific to the tenant they were created
under.
By default, Laravel is set to use the `database` session driver, and it creates the table in a
[default migration](https://github.com/laravel/laravel/blob/11.x/database/migrations/0001_01_01_000000_create_users_table.php#L30-L37)
that comes with the Laravel installation.
If you plan to continue with this driver, you'll need to add two columns to the `sessions` table.

```php
$table->string('tenancy')->nullable();
$table->bigInt('tenant_id')->nullable();
```

> [!NOTE]
> The `tenant_id` column should match the primary key of your tenant model, which by default within Laravel would
> be `BIGINT`, which is why `bigInt()` is used here.

The only other thing to consider here is that if you encounter CSRF issues when you first get started, clear your
cookies in your browser, and it'll fix the issue.
Unfortunately, this is a bug to do with Laravel's cookie handling if you access a CSRF protected route before Sprout
is fully setup and working.
Sadly, it's not something that Sprout can fix.

### Tenant Child Models

You've got your tenant, Laravel knows how to identify it, and which routes should be multitenanted.
Everything is all working and ready to go, except your tenant model needs some child models.
Since the tenant is `Blog`, it makes sense that one of its child models would be `Post`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
```

All that we need to do to hook this model into the multitenancy functionality is add the `BelongsToTenant` trait,
and mark the tenant relation with the `TenantRelation` attribute.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Sprout\Attributes\TenantRelation;
use Sprout\Database\Eloquent\Concerns\BelongsToTenant;

class Post extends Model
{
    use BelongsToTenant; // [tl! ++]
    
    #[TenantRelation] // [tl! ++]
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }
}
```

This model is now part of the multitenancy functionality.
All `Post` that are loaded will automatically be scoped to the current `Blog`, and any new `Post` created will
automatically be associated with the current `Blog`.

> [!TIP]
> It may be worth creating a base abstract `BlogModel` that defines your tenant relation and uses the trait, to
> avoid repeating yourself.
> Then all your child models can extend it and inherit the multitenancy configuration.

## What's next?

You've now got a multitenanted Laravel application, and you can get on with building it.
It's possible for you to carry on without ever touching Sprout again, besides the odd model trait here or there.
However, there is a lot more that's available to you, so at the very least, it's worth exploring what Sprout has to
offer.

> [!CALLOUT]
> ### Configuration
> Sprout comes with a couple of configuration files, that allow you to customise how Sprout interacts with your
> application, and how it works at its core. 
> Most of the configuration can be left as is, but it's worth exploring what it's all for.
> 
> [Read more about Configuration](#){:class="btn btn--sm btn--guttered"}

> [!CALLOUT]
> ### Tenant Resolution
> Sprout comes with a number of resolvers that can be used to identify your tenant,
> as well as the ability to create your own.
> It's worth exploring these, so you can figure out which ones you need, as Sprout lets you use as many as you need.
> 
> [Read more about Tenant Resolution](#){:class="btn btn--sm btn--guttered"}

> [!CALLOUT]
> ### Service Overrides
> Sprout comes with several service overrides that allow you to make Laravels core services tenant-aware, with
> the ability to create your own.
> Read about the ones that are enabled by default, how you can best use them.
> 
> [Read more about Service Overrides](#){:class="btn btn--sm btn--guttered"}
