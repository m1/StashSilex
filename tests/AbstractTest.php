<?php

namespace M1\StashSilex\Test;

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    public $tmp_folder;
    public $app;

    public function rmtree($path)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $filename => $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($filename);
            } else {
                unlink($filename);
            }
        }

        rmdir($path);
    }
    
    public function isDirEmpty($dir)
    {
        $iterator = new \FilesystemIterator($dir);
        return !$iterator->valid();
    }

    protected function setUp()
    {
        $this->tmp_folder = __DIR__.'/tmp';
        $this->setupFolder($this->tmp_folder);

        $app = new \Silex\Application();

        $app['session.storage'] = new MockArraySessionStorage();
        $app['session.test'] = true;

        $this->app = $app;
    }

    public function setupFolder($path)
    {
        if (file_exists($path)) {
            $this->rmtree($path);
        }

        mkdir($path, 0777);
    }

    protected function tearDown()
    {
        $this->tearDownFolder($this->tmp_folder);
    }

    public function tearDownFolder($path)
    {
        if (file_exists($path)) {
            $this->rmtree($path);
        }
    }
}