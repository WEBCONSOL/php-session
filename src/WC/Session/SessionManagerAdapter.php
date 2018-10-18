<?php

namespace WC\Session;

interface SessionManagerAdapter
{
    public function getId();

    public function delete(string $key);

    public function destroy();

    public function gc();

    public function get(string $key, $default=null);

    public function set(string $key, $value);

    public function start(string $sid, int $lifetime);
}