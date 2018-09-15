<?php

namespace WC\Session;

use Database\Driver;
use WC\Models\ListModel;

class SessionManager
{
    private $login_user_id = "logged_in_user_id";
    private $tbl = 'php_session';
    private $fld_id = 'id';
    private $fld_session_id = 'session_id';
    private $fld_user_id = 'user_id';
    private $fld_session_data = 'session_data';
    private $fld_modified_datetime = 'last_modified_datetime';
    private $fld_created_datetime = 'created_datetime';
    private $session_lifetime =  60*60*24;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Driver
     */
    private $em;

    /**
     * @var int
     */
    private $now = 0;

    /**
     * @var array
     */
    private $session = array();

    private $db = false;

    /**
     * SessionManager constructor.
     *
     * @param Driver $em
     * @param string $id
     */
    public function __construct(Driver $em, string $id = "")
    {
        $this->em = $em;
        $this->now = strtotime('now');
        $this->id = $id;
        $this->start();
    }

    /**
     * @param $path
     * @param $name
     *
     * @return bool
     */
    public function open($path, $name) {return ($this->em instanceof Driver);}

    /**
     * @return bool
     */
    public function close(){$this->gc();return true;}

    /**
     * @param $id
     *
     * @return array
     */
    public function read(string $id): array
    {
        if ($this->isValidId($id) && $this->db) {
            $query = 'SELECT * FROM '.$this->tbl.' WHERE ' . $this->fld_session_id . '=' . $this->em->quote($id);
            $result = $this->em->loadResult($query);
            if (sizeof($result)) {
                $this->session = $this->fromValue($result[$this->fld_session_data]);
                return $this->session;
            }
        }
        else {
            $session = isset($_SESSION) ? $_SESSION : array();
            foreach ($session as $key=>$value) {
                $session[$key] = $this->get($key);
            }
            return $session;
        }
        return array();
    }

    /**
     * @param string $sessionId
     * @param mixed  $data
     * @param int    $userId
     *
     * @return bool|mixed
     */
    public function write(string $sessionId, $data, int $userId=0)
    {
        if ($this->isValidId($sessionId) && !empty($data) && $this->db)
        {
            $data = $this->unserialize($data);
            $userId = $this->get($this->login_user_id, $userId);

            if (!empty($this->read($sessionId)))
            {
                $query = 'UPDATE ' . $this->tbl . ' SET '
                    . $this->fld_session_data . '=' . $this->em->quote($this->toValue($data)) . ','
                    . $this->fld_created_datetime . '=' . $this->em->quote(strtotime('now')) . ','
                    . $this->fld_modified_datetime . '=' . $this->em->quote(strtotime('now')) . ','
                    . $this->fld_user_id . '=' . $this->em->quote($userId)
                    . ' WHERE ' . $this->fld_session_id . '=' . $this->em->quote($sessionId);
            }
            else
            {
                $fields = array($this->fld_session_id,$this->fld_session_data,$this->fld_created_datetime,$this->fld_modified_datetime);
                $values = array($this->em->quote($sessionId), $this->em->quote(json_encode($data)), $this->em->quote(strtotime('now')), $this->em->quote(strtotime('now')));
                if (!empty($userId)) {
                    $fields[] = $this->fld_user_id;
                    $values[] = $this->em->quote($userId);
                }
                $query = 'INSERT INTO ' . $this->tbl . '('. implode(',', $fields). ') VALUES(' . implode(',', $values) .')';
            }

            $this->em->executeStatement($query);
        }
        else if (!empty($data))
        {
            $data = $this->unserialize($data);
            foreach ($data as $k=>$v) {
                $this->set($k, $v);
            }
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return bool|mixed
     */
    public function destroy(string $id="")
    {
        session_destroy();

        if ($this->isValidId($id) && $this->db) {
            $query = 'DELETE FROM ' . $this->tbl . ' WHERE ' . $this->fld_session_id . '=' . $this->em->quote($id);
            $this->em->executeStatement($query);
        }

        return true;
    }

    /**
     * void
     */
    public function gc()
    {
        if ($this->db) {
            $query = 'DELETE FROM ' . $this->tbl . ' WHERE ' .
                $this->fld_modified_datetime . '<' . $this->em->quote(strtotime('now') - $this->session_lifetime);
            $this->em->executeStatement($query);
        }
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getUserSession(int $id): array
    {
        if ($this->db) {
            $query = 'SELECT * FROM ' . $this->tbl . ' WHERE ' . $this->fld_user_id . '=' . $this->em->quote($id);
            return $this->em->loadResult($query);
        }
        else {
            $loginId = $this->get($this->login_user_id, 0);
            if ($loginId) {
                $sessionData = $this->get($this->fld_session_data);
                if (is_string($sessionData)) {
                    $sessionData = $this->fromValue($sessionData);
                }
                if (is_array($sessionData) && isset($sessionData[$this->fld_id])) {
                    if (is_numeric($loginId) && (int)$sessionData[$this->fld_id] === (int)$loginId) {
                        return $sessionData;
                    }
                    else if (is_string($loginId) && $sessionData[$this->fld_id] === $loginId) {
                        return $sessionData;
                    }
                }
            }
            return array();
        }
    }

    /**
     * @return string
     */
    public function getId() {return session_id();}

    /**
     * @param $k
     *
     * @return bool
     */
    public function has($k): bool {return (is_array($_SESSION)&&isset($_SESSION[$k]))||(isset($this->session[$k]));}

    /**
     * @param string $k
     * @param string $default
     *
     * @return mixed|string
     */
    public function get(string $k, $default = "")
    {
        if (isset($this->session[$k])) {
            return $this->fromValue($this->session[$k]);
        }
        if (is_array($_SESSION) && isset($_SESSION[$k])) {
            return $this->fromValue($_SESSION[$k]);
        }
        return $default;
    }

    /**
     * @param string $k
     * @param        $v
     */
    public function set(string $k, $v)
    {
        $this->session[$k] = $this->toValue($v);
        $_SESSION[$k] = $this->session[$k];
    }


    /**
     * @param string $id
     * @param array  $data
     * @param int    $userId
     */
    public function restart(string $id, array $data, $userId=0)
    {
        if (session_status() === PHP_SESSION_ACTIVE && $id != session_id())
        {
            $this->destroy(session_id());
        }
        if (!session_id($id))
        {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            new \Exception("Session restart, but session is still not active.");
        }

        if (!empty($data))
        {
            $data = $this->fromValue($this->toValue($data));

            foreach ($data as $k=>$v)
            {
                $this->set($k, $v);
            }

            if ($userId > 0 && !$this->has($this->login_user_id))
            {
                $this->set($this->login_user_id, $userId);
            }
        }
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    public function unserialize(string $session_data): array
    {
        $output = array();
        $method = ini_get("session.serialize_handler");

        switch ($method)
        {
            case "php":
                $output = $this->unserialize_php($session_data);
                break;

            case "php_binary":
                $output = $this->unserialize_phpbinary($session_data);
                break;

            default:
                new \Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }

        return $output;
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    private function unserialize_php(string $session_data): array
    {
        $return_data = array();
        $offset = 0;

        while ($offset < strlen($session_data))
        {
            if (!strstr(substr($session_data, $offset), "|")) {
                new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    private function unserialize_phpbinary(string $session_data): array
    {
        $return_data = array();
        $offset = 0;

        while ($offset < strlen($session_data))
        {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }


    /**
     * void
     */
    private function start()
    {
        if ($this->db) {
            session_set_save_handler (
                array($this, "open"),
                array($this, "close"),
                array($this, "read"),
                array($this, "write"),
                array($this, "destroy"),
                array($this, "gc")
            );
            register_shutdown_function('session_write_close');
            $this->gc();
        }

        if (!empty($this->id)) {
            if (!session_id($this->id)) {
                session_start();
            }
        }
        else if (!session_id()) {
            session_start();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            new \Exception("Session start, but session is still not active.");
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    private function isValidId(string $id): bool{return !empty($id) ? preg_match('/^[-,a-zA-Z0-9]{1,32}$/', $id) > 0 : false;}

    /**
     * @param string $val
     *
     * @return array
     */
    public function fromValue(string $val): array
    {
        if (is_object($val)) {
            return json_decode(json_encode($val), true);
        }
        if (is_array($val)) {
            return $val;
        }
        if (empty($val)) {
            return array();
        }
        $json = json_decode($val, true);
        return is_array($json) ? $json : ($val ? array($val) : array());
    }

    /**
     * @param mixed $val
     *
     * @return string
     */
    public function toValue($val): string
    {
        if (is_array($val) || is_object($val)) {
            return json_encode($val);
        }
        if (is_numeric($val)) {
            return "".$val;
        }
        return $val;
    }

    public function login(ListModel $userObject)
    {
        if ($this->get($this->login_user_id) != $userObject->get($this->fld_id))
        {
            $userSession = $this->getUserSession($userObject->get($this->fld_id));

            if (!empty($userSession))
            {
                $sid = $userSession[$this->fld_session_id];
                $data = $this->fromValue($userSession[$this->fld_session_data]);

                if ($data[$this->login_user_id] == $userObject->get($this->fld_id))
                {
                    $this->restart($sid, $data, $userObject->get($this->fld_id));
                }
                else
                {
                    $this->destroy($sid);
                    $this->set($this->login_user_id, $userObject->get($this->fld_id));
                    $this->set($this->fld_session_data, $userObject->jsonSerialize());
                }
            }
            else
            {
                $this->set($this->login_user_id, $userObject->get($this->fld_id));
                $this->set($this->fld_session_data, $userObject->jsonSerialize());
            }
        }
    }

    public function isLogin(): bool {return sizeof($this->getData()) ? true : false;}

    public function getData(): array {
        $sessionId = session_id();
        $data = $this->read($sessionId);
        if (is_array($data) && isset($data[$this->fld_session_data]) && isset($data[$this->login_user_id])) {
            if (is_string($data[$this->fld_session_data])) {
                $data[$this->fld_session_data] = json_decode($data[$this->fld_session_data], true);
            }
            if ($data[$this->fld_session_data][$this->fld_id] === $data[$this->login_user_id]) {
                return $data;
            }
        }
        return array();
    }
}