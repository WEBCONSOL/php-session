<?php

namespace WC\Session\Adapters;

use WC\Database\Driver;
use WC\Models\UserModel;
use WC\Session\AuthenticationAdapter;
use WC\Session\Helpers\UserGroup;
use WC\Session\SessionManager;

class Authenticator implements AuthenticationAdapter
{
    private $em;
    private $config;

    public function __construct(Driver &$em, array $config=array()) {
        $this->em =& $em;
        $this->config = $config;
    }

    public function login(string $username, string $password, SessionManager $sessionManager): UserModel {
        if ($this->isLogin($sessionManager)) {
            $user = $sessionManager->get(WC_SESSION_DATA_KEY, new UserModel(array()));
        }
        else {
            $user = new UserModel(array());
            UserGroup::fetchUser($username, $this->em, $user);
            if ($user->isNotEmpty() && ($user->is('password', $password) || $user->is('password', md5($password)))) {
                UserGroup::fetchUserGroups($user, $this->em);
                UserGroup::fetchUserPermissions($user, $this->em);
                $uid = $user->getId();
                setcookie(WC_SESSION_LOGIN_KEY, $uid, 0, "/");
                $sessionManager->set(WC_SESSION_LOGIN_KEY, $uid);
                $sessionManager->set(WC_SESSION_DATA_KEY, $user);
            }
        }
        return $user;
    }

    public function logout($idOrUsername, SessionManager $sessionManager): bool {
        if ($this->isLogin($sessionManager)) {
            $userData = null;
            if (!$idOrUsername) {
                $userData = $sessionManager->get(WC_SESSION_DATA_KEY, new UserModel(array()));
            }
            else if (is_numeric($idOrUsername)) {
                $uid = (int)$sessionManager->get(WC_SESSION_LOGIN_KEY, 0);
                if ($uid === (int)$idOrUsername) {
                    $userData = $sessionManager->get(WC_SESSION_DATA_KEY, new UserModel(array()));
                }
            }
            else if (is_string($idOrUsername)) {
                $userData = $sessionManager->get(WC_SESSION_DATA_KEY, new UserModel(array()));
                if (!($userData instanceof UserModel) || $userData->getUserName() !== $idOrUsername) {
                    $userData = null;
                }
            }
            if (!($userData instanceof UserModel) || ($userData instanceof UserModel && $userData->isNotEmpty())) {
                $sessionManager->delete(WC_SESSION_LOGIN_KEY);
                $sessionManager->delete(WC_SESSION_DATA_KEY);
                setcookie(WC_SESSION_LOGIN_KEY, 0, -1);
                return true;
            }
            return false;
        }
        return true;
    }

    public function isLogin(SessionManager $sessionManager): bool {
        $uid = (int)$sessionManager->get(WC_SESSION_LOGIN_KEY, 0);
        $userData = $sessionManager->get(WC_SESSION_DATA_KEY);
        return $uid > 0 && $userData instanceof UserModel && (int)$userData->getId() === $uid;
    }
}