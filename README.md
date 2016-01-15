# StashSilex

[![Author](http://img.shields.io/badge/author-@milescroxford-blue.svg?style=flat-square)](https://twitter.com/milescroxford)
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
<!--[![Total Downloads][ico-downloads]][link-downloads]-->

StashSilex is a service provider and session handler for the popular caching library [Stash](http://www.stashphp.com/index.html).

## Requirements

StashSilex requires PHP version `5.3+`.

## Install

Via Composer

``` bash
$ composer require M1/StashSilex
```

## Usage

You use the StashServiceProvider to register the service provider with the usual syntax for registering service providers:

``` php
$app->register(new M1\StashSilex\StashServiceProvider());
```

There's a few options you can use when registering the service provider.

### Pools

You can register either one pool using `pool.options` or multiple using `pools.options`, this works like the 
[Doctrine Service Provider](http://silex.sensiolabs.org/doc/providers/doctrine.html).

Registering one pool:
``` php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
    )
));
```

Registering multiple pools:
```php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pools.options' => array(
        'fs' => array(
            'driver' => 'FileSystem',
            'options' => array(
                'path' => __DIR__.'/../../app/cache',
            ),
        ),
        'mc' => array(
            'driver' => 'Memcache',
            'options' => array(
                'servers' => array(
                    '127.0.0.1', '11211'
                )
            ),
        ),
    ),
));
```

You can access your pools through `$app['pool']` and `$app['pools']['the_key_of_your_pool']`. If you have multiple pools, then your 
first pool registered will be available through `$app['pool']`. 

For example, in the above code, the `FileSystem` pool will be available through `$app['pool']` and `$app['pools']['fs']`.

### Drivers

The driver option is based on the class names for the available drivers, [see here](http://www.stashphp.com/Drivers.html) 
for the class names defined by Stash. The driver names are case sensitive.

You can set the driver options through `options` like so:

``` php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
    )
));
```

You can see the full list of the available options for each driver [here](http://www.stashphp.com/Drivers.html). 
The default driver if no driver is defined is the `Ephemeral` driver.

### Logger

You can also set the logger for the pool via the `logger` option like so:

``` php

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../../app/logs/app/dev.log',
));

$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
        'logger' => 'monolog'
    )
));
```

The logger is `monolog` due to the `MonologServiceProvider` populating `$app['monolog']`. The logger option is a string 
which your logger service can be accessed through `$app`. 

For example if you decided to not use `Monolog` through the service provider (not recommended), you can use your custom logger like so:

```php
$app['mylog'] = $app->share(function($app) {
    $logger = new \Monolog\Logger('mylog');
    $logger->pushHandler(new Monolog\Handler\StreamHandler('/logfile/mylog.log', Logger::INFO));
    return $logger;
});

$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
        'logger' => 'mylog'
    )
));
```

### Sessions

You can choose to handle your sessions through Stash in a couple of different ways.

The first way is via the service provider.

The below creates sessions with defaults:
```php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
        'session' => true
    )
));
```

You can also set the `ttl` and the `session prefix` (what namespace it is stored in in stash, more info [here](http://www.stashphp.com/Grouping.html)) like so:

```php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        ),
        'session' => array(
            'prefix' => 'session_name',
            'expiretime' => 3200
        )
    )
));
```

You can also set the `SessionHandler` manually via:

```php
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pool.options' => array(
        'driver' => 'FileSystem',
        'options' => array(
            'path' => __DIR__.'/../../app/cache',
        )
    )
));

// Without options
$app['session.storage.handler'] = $app->share(function ($app) {
    return new M1\StashSilex\StashSessionHandler($app['pool']);
});

// With options
$app['session.storage.handler'] = $app->share(function ($app) {
    return new M1\StashSilex\StashSessionHandler($app['pool'], array(
        'prefix' => 'session_name',
        'expiretime' => 3200
    ));
});

```

### Recommendations

Instead of setting the options through an array, think about using a config loader like [`m1/vars`](https://github.com/m1/vars), 
where you can just load the configuration of your pool(s) via a file like so:

``` yaml
# example.yml
pools:
    filesystem:
        driver: FileSystem
        options:
            path: %dir%/../../app/cache
        session:
            prefix: session_name
            expiretime: 3200
```

``` php

$app->register(new M1\Vars\Provider\Silex\VarsServiceProvider('example.yml'), array(
    'vars.options' => array(
        'variables' => array(
            'dir' => __DIR__
        ),
)));
    
$app->register(new M1\StashSilex\StashServiceProvider(), array(
    'pools.options' => $app['vars']['pools']
));
```

This way makes it so much easier to make small little changes without having to dive into code.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email hello@milescroxford.com instead of using the issue tracker.

## Credits

- [Miles Croxford][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/m1/stash-silex.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/m1/StashSilex/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/M1/StashSilex.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/M1/StashSilex.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/M1/StashSilex.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/M1/StashSilex
[link-travis]: https://travis-ci.org/M1/StashSilex
[link-scrutinizer]: https://scrutinizer-ci.com/g/M1/StashSilex/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/M1/StashSilex
[link-downloads]: https://packagist.org/packages/M1/StashSilex
[link-author]: https://github.com/m1
[link-contributors]: ../../contributors
