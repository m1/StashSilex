<?php

/**
 * This file is part of the m1\stash-silex library
 *
 * (c) m1 <hello@milescroxford.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package     m1/stash-silex
 * @version     0.1.0
 * @author      Miles Croxford <hello@milescroxford.com>
 * @copyright   Copyright (c) Miles Croxford <hello@milescroxford.com>
 * @license     http://github.com/m1/stashsilex/blob/master/LICENSE
 * @link        http://github.com/m1/stashsilex/blob/master/README.MD Documentation
 */

namespace M1\StashSilex;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * The StashServiceProvider provides the stash service to silex
 *
 * @since 0.1.0
 */
class StashServiceProvider implements ServiceProviderInterface
{
    /**
     * The session handler before this service provider checks to see if it should set a session handler
     *
     * @var null
     */
    private $session_handler;

    /**
     * Registers the service provider, sets the user defined options and sets the session handler
     *
     * @param \Silex\Application $app The silex app
     */
    public function register(Application $app)
    {
        $app['pool.default_options'] = array(
            'driver'  => 'Ephemeral',
            'session' => false,
            'logger'  => false,
        );

        $app['pools.options.init'] = $app->protect(function () use ($app) {
            static $init = false;

            if ($init) {
                return;
            }

            $init = true;

            $app['pools.options'] = $this->createOptions($app);
        });

        $app['pools'] = $app->share(function () use ($app) {
            $app['pools.options.init']();

            return $this->createPools($app);

        });

        $this->session_handler = isset($app['session.storage.handler']) ? $app['session.storage.handler'] : array();

        $app['session.storage.handler'] = $app->share(function () use ($app) {
            $app['pools.options.init']();

            foreach ($app['pools.options'] as $name => $options) {
                if (isset($options['session']) && $options['session']) {
                    $session_options = (is_array($options['session'])) ? $options['session'] : array();

                    return new StashSessionHandler($app['pools'][$name], $session_options);
                }
            }

            return $this->session_handler;
        });

        $app['pool'] = $app->share(function ($app) {
            $pools = $app['pools'];

            return $pools[$app['pools.default']];
        });
    }

    /**
     * The silex service provider boot function
     *
     * @param \Silex\Application $app The silex app
     *
     * @codeCoverageIgnore
     */
    public function boot(Application $app)
    {
    }

    /**
     * Creates and parses the options from the user options
     *
     * @param \Silex\Application $app The silex app
     *
     * @return array $options The parsed options
     */
    private function createOptions($app)
    {
        if (!isset($app['pools.options'])) {
            $app['pools.options'] = array(
                'default' => isset($app['pool.options']) ? $app['pool.options'] : array()
            );
        }

        $tmp = $app['pools.options'];
        $options = array();

        foreach ($tmp as $name => $opts) {
            $options[$name] = array_replace($app['pool.default_options'], $opts);

            if (!isset($app['pools.default'])) {
                $app['pools.default'] = $name;
            }
        }

        return $options;
    }

    /**
     * Creates the pools with user defined options
     *
     * @param \Silex\Application $app The silex app
     *
     * @return \Pimple $pools The pools in the pimple container
     */
    private function createPools($app)
    {
        $pools = new \Pimple();

        foreach ($app['pools.options'] as $name => $options) {
            $pool = new \Stash\Pool($this->fetchDriver($options));
            $pool->setLogger($this->fetchLogger($app, $options));

            $pools[$name] = $pools->share(function () use ($pool) {
                return $pool;
            });

        }

        return $pools;
    }

    /**
     * Fetches the driver if exists, else throws exception
     *
     * @param array $options The options for Stash
     *
     * @throws \InvalidArgumentException If the driver class can not be found
     *
     * @return mixed Instance of the driver class
     */
    private function fetchDriver($options)
    {
        $driver = sprintf('Stash\Driver\%s', $options['driver']);

        if (!class_exists($driver)) {
            throw new \InvalidArgumentException(sprintf('\'%s\' driver class does not exist', $driver));
        }

        $driver = new \ReflectionClass($driver);

        $driver_options = (isset($options['options'])) ? $options['options'] : array();
        $driver = $driver->newInstanceArgs(array($driver_options));

        return $driver;
    }

    /**
     * Fetches the driver if exists, else throws exception
     *
     * @param \Silex\Application $app The silex app
     * @param array $options The options for Stash
     *
     * @throws \InvalidArgumentException If the logger service does not exist
     *
     * @return mixed The logger service
     */
    private function fetchLogger($app, $options)
    {
        if (isset($options['logger']) && $options['logger']) {
            $logger = $options['logger'];

            if (!isset($app[$logger])) {
                throw new \InvalidArgumentException(
                    sprintf('\'%s\' logger not defined as service in app container', $logger)
                );
            }

            return $app[$options['logger']];
        }

        return null;
    }
}