<?php
require_once __DIR__ . '/SessionContainerInterface.php';
require_once __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using Cookie based Session Container
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class CookieBasedSessionContainer extends SessionContainerHelper implements SessionContainerInterface
{
    public function init($sessionSavePath, $sessionName)
    {
        if (empty($this->passphrase) || empty($this->iv)) {
            die ('Please set encryption details in Session.php');
        }

        $this->currentTimestamp = time();
    }

    public function get($sessionId)
    {
        if (isset($_COOKIE[$this->sessionDataName]) && !empty($_COOKIE[$this->sessionDataName])) {
            $sessionData = $this->decryptData($_COOKIE[$this->sessionDataName]);
            $sessionDataArr = unserialize($sessionData);
            if (
                isset($sessionDataArr['_TS_']) &&
                ($sessionDataArr['_TS_'] + $this->sessionMaxlifetime) > $this->currentTimestamp
            ) {
                return $sessionData;
            }
        }
        return false;
    }

    public function set($sessionId, $sessionData)
    {
        $sessionDataArr = unserialize($sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize($sessionDataArr);

        $cookieData = $this->encryptData($sessionData);
        if (strlen($cookieData) > 4096) {
            ob_end_clean();
            die('Session data length exceeds max 4 kilobytes (KB) supported per Cookie');
        }

        $_COOKIE[$this->sessionDataName] = $cookieData;
        
        return setcookie(
            $name = $this->sessionDataName,
            $value = $cookieData,
            $options = [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => ((strpos($_SERVER['HTTP_HOST'], 'localhost') === false) ? true : false),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function touch($sessionId, $sessionData)
    {
        $sessionDataArr = unserialize($sessionData);
        $sessionDataArr['_TS_'] = $this->currentTimestamp;
        $sessionData = serialize($sessionDataArr);

        $cookieData = $this->encryptData($sessionData);
        if (strlen($cookieData) > 4096) {
            ob_end_clean();
            die('Session data length exceeds max 4 kilobytes (KB) supported per Cookie');
        }

        $_COOKIE[$this->sessionDataName] = $cookieData;
        
        return setcookie(
            $name = $this->sessionDataName,
            $value = $cookieData,
            $options = [
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => ((strpos($_SERVER['HTTP_HOST'], 'localhost') === false) ? true : false),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    public function gc($sessionMaxlifetime)
    {
        return true;
    }

    public function delete($sessionId): bool
    {
        return true;
    }

    public function close()
    {
    }
}
