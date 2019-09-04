<?php

namespace WC\Session\Adapters;

use Doctrine\ORM\EntityManager;
use WC\Session\SessionManagerAdapter;

class Database implements SessionManagerAdapter
{
    public static $TB_NAME = 'sessions';
    private $em = null;
    private $id = '';

    public function __construct(EntityManager &$em)
    {
        $this->em = $em;
    }

    public function getId()
    {
        return $this->id;
    }

    public function start(string $sid, int $lifetime)
    {
        $query = '';
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