<?php

namespace WC\Session\Adapters;

use WC\Session\SessionManagerAdapter;

class Memcache implements SessionManagerAdapter
{
    public function __construct()
    {
    }

    public function start(string $sid, int $lifetime)
    {
        // TODO: Implement start() method.
    }

    public function set(string $key, $value)
    {
        // TODO: Implement set() method.
    }

    public function get(string $key, $default=null)
    {
        // TODO: Implement get() method.
    }

    public function gc()
    {
        // TODO: Implement gc() method.
    }

    public function destroy()
    {
        // TODO: Implement destroy() method.
    }

    public function delete(string $key)
    {
        // TODO: Implement delete() method.
    }
}