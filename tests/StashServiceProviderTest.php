<?php

namespace M1\StashSilex\Test;

class StashServiceProviderTest extends AbstractTest
{
    public function testBasicStashServiceProvider()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver' => 'FileSystem',
                'options' => array(
                    'path' => $this->tmp_folder
                ),
            )
        ));

        $this->assertTrue($this->isDirEmpty($this->tmp_folder));
        $this->assertInstanceOf('\Stash\Pool', $app['pool']);

        $item = $app['pool']->getItem('hello');
        $data = $item->get();

        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertNull($data);
        $this->assertTrue($item->isMiss());

        $item->set('test', 1200);

        $this->assertFalse($this->isDirEmpty($this->tmp_folder));

        $item = $app['pool']->getItem('hello');
        $data = $item->get();

        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertEquals('test', $data);
    }

    public function testDefaultDriver()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider());

        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['pool']);
    }

    public function testDefaultDriverEmptyPool()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.option' => array()
        ));

        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['pool']);
    }

    public function testDefaultDriverEmptyPools()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.option' => array()
        ));

        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['pool']);
    }

    public function testDefaultDriverEmptyDriver()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.option' => array(
                'e' => array()
            )
        ));

        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['pool']);
    }

    public function testMultiplePoolServiceProvider()
    {
        $app = $this->app;

        $tmp_folder2 = __DIR__.'/tmp2';

        $this->setupFolder($tmp_folder2);

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.options' => array(
                'fs1' => array(
                    'driver' => 'FileSystem',
                    'options' => array(
                        'path' => $this->tmp_folder
                    ),
                ),
                'fs2' => array(
                    'driver' => 'FileSystem',
                    'options' => array(
                        'path' => $tmp_folder2
                    ),
                ),
            )
        ));

        $this->assertInstanceOf('\Pimple', $app['pools']);
        $this->assertInstanceOf('\Stash\Pool', $app['pool']);
        $this->assertInstanceOf('\Stash\Pool', $app['pools']['fs1']);
        $this->assertInstanceOf('\Stash\Pool', $app['pools']['fs2']);
        $this->assertEquals($app['pool'], $app['pools']['fs1']);

        $this->assertTrue($this->isDirEmpty($this->tmp_folder));

        $item = $app['pools']['fs1']->getItem('hello');
        $data = $item->get();

        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertNull($data);
        $this->assertTrue($item->isMiss());

        $item->set('test', 1200);

        $this->assertTrue($this->isDirEmpty($tmp_folder2));
        $item = $app['pools']['fs2']->getItem('hello');
        $data = $item->get();

        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertNull($data);
        $this->assertTrue($item->isMiss());

        $item->set('test2', 1200);


        $item = $app['pools']['fs1']->getItem('hello');
        $data = $item->get();
        $this->assertFalse($item->isMiss());
        $this->assertFalse($this->isDirEmpty($this->tmp_folder));

        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertEquals('test', $data);

        $item = $app['pools']['fs2']->getItem('hello');
        $data = $item->get();
        $this->assertFalse($item->isMiss());
        $this->assertFalse($this->isDirEmpty($tmp_folder2));
        $this->assertInstanceOf('\Stash\Item', $item);
        $this->assertEquals('test2', $data);

        $this->tearDownFolder($tmp_folder2);
    }

    public function testMultipleDrivers()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.options' => array(
                'fs1' => array(
                    'driver'  => 'FileSystem',
                    'options' => array(
                        'path' => $this->tmp_folder
                    ),
                ),
                'ep' => array(
                    'driver'  => 'Ephemeral',
                ),
            )
        ));

        $this->assertAttributeInstanceOf('\Stash\Driver\FileSystem', 'driver', $app['pools']['fs1']);
        $this->assertAttributeInstanceOf('\Stash\Driver\Ephemeral', 'driver', $app['pools']['ep']);
    }

    public function testSetLogger()
    {
        $app = $this->app;

        $app->register(new \Silex\Provider\MonologServiceProvider(), array(
            'monolog.logfile' => $this->tmp_folder.'/stash.log',
        ));

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pools.options' => array(
                'fs1' => array(
                    'driver'  => 'FileSystem',
                    'options' => array(
                        'path' => $this->tmp_folder
                    ),
                    'logger' => 'monolog'
                )
            )
        ));

        $this->assertAttributeInstanceOf('\Monolog\Logger', 'logger', $app['pools']['fs1']);
    }

    public function testNoLogger()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'logger' => ''
            )
        ));

        $this->assertAttributeEmpty('logger', $app['pool']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage 'Stash\Driver\NONE' driver class does not exist
     */
    public function testNotDriverClass()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'driver'  => 'NONE',
            )
        ));

        $item = $app['pools']['fs1']->getItem('hello');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage 'monolog' logger not defined as service in app container
     */
    public function testLoggerNotDefined()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider(), array(
            'pool.options' => array(
                'logger' => 'monolog'
            )
        ));

        $item = $app['pool']->getItem('hello');
    }


    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Identifier "not_a_pool" is not defined.
     */
    public function testPoolNotDefined()
    {
        $app = $this->app;

        $app->register(new \M1\StashSilex\StashServiceProvider());

        $item = $app['pools']['not_a_pool']->getItem('hello');
    }
}
