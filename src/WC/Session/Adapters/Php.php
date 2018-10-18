<?php

namespace WC\Session\Adapters;

use WC\Session\Helpers\Converter;
use WC\Models\SessionModel;
use WC\Session\SessionManagerAdapter;

final class Php implements SessionManagerAdapter
{
    private $id = '';

    public function getId()
    {
        return $this->id;
    }

    public function delete(string $key)
    {
        $session = $this->getRoot();
        $session->delete($key);
        $this->setRoot($session);
    }

    public function destroy()
    {
        $session = $this->getRoot();
        $session->reset();
        $this->setRoot($session);
        session_destroy();
    }

    public function gc()
    {
        $session = $this->getRoot();
        if ($session->has(WC_SESSION_KEY_LIFETIME)) {
            $n = $session->get(WC_SESSION_KEY_LIFETIME);
            if ($n < strtotime("now")) {
                $session->reset();
                $this->setRoot($session);
            }
        }
    }

    public function get(string $key, $default=null)
    {
        $session = $this->getRoot();
        return $session->get($key, $default);
    }

    public function set(string $key, $value)
    {
        $session = $this->getRoot();
        $session->set($key, $value);
        $this->setRoot($session);
    }

    public function start(string $sid, int $lifetime)
    {
        if ($sid) {
            $this->id = $sid;
            session_id($sid);
        }
        else {
            $this->id = session_id();
        }
        session_start();
        $session = $this->getRoot();
        if (!$session->has(WC_SESSION_KEY_LIFETIME)) {
            $session->set(WC_SESSION_KEY_LIFETIME, strtotime("now")+$lifetime);
            $this->setRoot($session);
        }
        else {
            $this->gc();
        }
    }

    private function getRoot(): SessionModel
    {
        if (isset($_SESSION[WC_SESSION_KEY_ROOT])) {
            return Converter::fromSessionValue($_SESSION[WC_SESSION_KEY_ROOT]);
        }
        return new SessionModel(array());
    }

    private function setRoot(SessionModel $session) {$_SESSION[WC_SESSION_KEY_ROOT] = Converter::toSessionValue($session);}
}