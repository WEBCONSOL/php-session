<?php

namespace WC\Session\Adapters;

use Database\Driver;
use WC\Session\SessionManagerAdapter;

class Database implements SessionManagerAdapter
{
    private $em = null;

    public function __construct(Driver $em)
    {
        $this->em = $em;
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