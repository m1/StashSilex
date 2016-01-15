<?php

namespace M1\StashSilex\Test;

class StashSessionHandlerTest extends AbstractTest
{
    public function testBasicStashServiceProvider()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
                'session' => true
            )
        ));

        $this->assertNull($app['session']->get('hello'));

        $app['session']->set('hello', 'test');
        $this->assertEquals('test', $app['session']->get('hello'));

        $this->assertInstanceOf('\M1\StashSilex\StashSessionHandler', $app['session.storage.handler']);
        $this->assertAttributeInstanceOf('\Stash\Pool', 'pool', $app['session.storage.handler']);

        $this->assertAttributeEquals(1800, 'ttl', $app['session.storage.handler']);
        $this->assertAttributeEquals('sessions', 'prefix', $app['session.storage.handler']);

        $this->assertAttributeInstanceOf('\Stash\Driver\FileSystem', 'driver', $app['session.storage.handler']->getPool());
    }

    public function testSessionOptions()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
                'session' => array(
                    'prefix' => 's1',
                    'expiretime' => 3200
                )
            )
        ));

        $this->assertNull($app['session']->get('hello'));

        $app['session']->set('hello', 'test');
        $this->assertEquals('test', $app['session']->get('hello'));

        $this->assertInstanceOf('\M1\StashSilex\StashSessionHandler', $app['session.storage.handler']);
        $this->assertAttributeInstanceOf('\Stash\Pool', 'pool', $app['session.storage.handler']);

        $this->assertAttributeEquals(3200, 'ttl', $app['session.storage.handler']);
        $this->assertAttributeEquals('s1', 'prefix', $app['session.storage.handler']);
    }

    public function testDefaultSession()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
                'session' => false
            )
        ));

        $this->assertNull($app['session']->get('hello'));

        $app['session']->set('hello', 'test');
        $this->assertEquals('test', $app['session']->get('hello'));

        $this->assertInstanceOf(
            '\Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler',
            $app['session.storage.handler']
        );
    }

    public function testOverrideSessionHandlerWithStash()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

         $app['session.storage.handler'] = $app->share(function ($app) {
             return new \Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler();
         });

            $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
                'session' => true
            )
            ));

            $this->assertNull($app['session']->get('hello'));

            $app['session']->set('hello', 'test');
            $this->assertEquals('test', $app['session']->get('hello'));

            $this->assertInstanceOf('\M1\StashSilex\StashSessionHandler', $app['session.storage.handler']);
            $this->assertAttributeInstanceOf('\Stash\Pool', 'pool', $app['session.storage.handler']);

            $this->assertAttributeEquals(1800, 'ttl', $app['session.storage.handler']);
            $this->assertAttributeEquals('sessions', 'prefix', $app['session.storage.handler']);
    }

    public function testOverrideSessionHandlerWithNative()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
                'session' => true
            )
        ));

        $app['session.storage.handler'] = $app->share(function ($app) {
            return new \Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler();
        });

        $this->assertNull($app['session']->get('hello'));

        $app['session']->set('hello', 'test');
        $this->assertEquals('test', $app['session']->get('hello'));

        $this->assertInstanceOf(
            '\Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler',
            $app['session.storage.handler']
        );
    }

    public function testSessionHandlerWithDefinedPool()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'Application',
                'cookie_lifetime' => 0,
                'cookie_httponly' => true,
            ),
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.options' => array(
                'fs1' => array(
                    'driver' => 'FileSystem',
                    'options' => array(
                        'path' => $this->tmp_folder,
                    ),
                ),
                'e' => array(
                    'driver' => 'Ephemeral',
                ),

            )
        ));

        $app['session.storage.handler'] = $app->share(function ($app) {
            return new \M1\StashSilex\StashSessionHandler($app['pools']['e']);
        });

        $this->assertNull($app['session']->get('hello'));

        $app['session']->set('hello', 'test');
        $this->assertEquals('test', $app['session']->get('hello'));

        $this->assertInstanceOf('\M1\StashSilex\StashSessionHandler', $app['session.storage.handler']);
        $this->assertAttributeInstanceOf('\Stash\Pool', 'pool', $app['session.storage.handler']);

        $this->assertAttributeEquals(1800, 'ttl', $app['session.storage.handler']);
        $this->assertAttributeEquals('sessions', 'prefix', $app['session.storage.handler']);

        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['session.storage.handler']->getPool());
    }
}
