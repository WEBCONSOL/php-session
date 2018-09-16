<?php

namespace WC\Session;

use WC\Models\UserModel;

final class SessionManager
{
    private static $adapter = null;
    private static $authenticator = null;

    public function __construct(SessionManagerAdapter $adapter=null, AuthenticationAdapter $authenticator=null) {
        if ($authenticator !== null && self::$authenticator === null) {
            self::$authenticator = $authenticator;
        }
        if (self::$adapter === null) {
            self::$adapter = $adapter;
            $this->start();
        }
    }

    public function delete(string $key){self::$adapter->delete($key);}

    public function destroy(){self::$adapter->destroy();}

    public function get(string $key, $default=null){return self::$adapter->get($key, $default);}

    public function set(string $key, $value){self::$adapter->set($key, $value);}

    public function login(string $username, string $password): UserModel {return self::$authenticator->login($username, $password, $this);}

    public function isLogin(): bool {return self::$authenticator->isLogin($this);}

    public function logout($idOrUsername=null): bool {return self::$authenticator->logout($idOrUsername, $this);}

    public function getSessionUserData(): UserModel {return $this->get(WC_SESSION_DATA_KEY, new UserModel(array()));}

    private function start() {
        include __DIR__ . "/constants.php";
        self::$adapter->start(WC_SESSION_ID, WC_SESSION_LIFETIME);
    }
}