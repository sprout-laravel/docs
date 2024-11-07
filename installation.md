# Installation
- [Requirements][1]
- [Install with Composer][2]
	- [Publish Assets][3]

## Requirements
Sprout requires the following to run:

- PHP 8.2+
- Laravel v11.30+
- Flysystem Path Prefixing v3.0+

## Install with Composer
Sprout can be installed by simply requiring the package through composer.

```bash
composer require sprout/sprout
```

> [!WARNING]()
> Sprout is currently in development, so if your composer is set to require stable releases, you won’t be able to install it until v1 launch.

### Publish Assets
Once you’ve installed Sprout you’ll want to publish its assets.

```bash
php artisan vendor:publish --provider="Sprout\\SproutServiceProvider"
```

## Next Steps
Your next steps will depend entire on the type of application you’re building, and how you want to build it. In effort to simplify the decisions required here, and point you in the right direction, we have a [Getting Started]() guide which will help you.

[1]:	#requirements
[2]:	#install-with-composer
[3]:	#publish-assets
