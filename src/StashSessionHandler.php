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

/**
 * The StashServiceProvider provides the stash service to silex
 *
 * @since 0.1.0
 */
class StashSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Stash\Pool Stash pool.
     */
    private $pool;

    /**
     * @var int Time to live in seconds
     */
    private $ttl;

    /**
     * @var string Key prefix for shared environments.
     */
    private $prefix;

    /**
     * Constructor
     *
     * @param \Stash\Pool $pool    A \Stash\Pool instance
     * @param array       $options An associative array of Stash options
     */
    public function __construct(\Stash\Pool $pool, array $options = array())
    {
        $this->pool = $pool;
        $this->ttl = isset($options['expiretime']) ? (int) $options['expiretime'] : 1800;
        $this->prefix = isset($options['prefix']) ? $options['prefix'] : 'sessions';
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function read($sessionId)
    {
        $item = $this->getItem($sessionId);
        $data = $item->get();

        return (!$item->isMiss()) ? $data : '';
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function write($sessionId, $data)
    {
        $item = $this->getItem($sessionId);
        $item->lock();

        return $item->set($data, $this->ttl);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function destroy($sessionId)
    {
        $item = $this->getItem($sessionId);

        return $item->clear();
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function gc($maxlifetime)
    {
        return $this->pool->purge();
    }

    /**
     * Return a Stash Pool instance.
     *
     * @return \Stash\Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    private function getItem($sessionId)
    {
        return $this->pool->getItem(sprintf('%s/%s', $this->prefix, $sessionId));
    }
}
