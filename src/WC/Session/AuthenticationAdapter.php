<?php

namespace WC\Session;

use WC\Database\Driver;
use WC\Models\UserModel;

interface AuthenticationAdapter
{
    public function __construct(Driver &$em, array $config=array());

    public function login(string $username, string $password, SessionManager $sessionManager): UserModel;

    public function logout($idOrUsername, SessionManager $sessionManager): bool;

    public function isLogin(SessionManager $sessionManager): bool;
}