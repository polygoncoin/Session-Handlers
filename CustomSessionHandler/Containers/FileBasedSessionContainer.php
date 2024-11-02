<?php
require_once __DIR__ . '/SessionContainerInterface.php';
require_once __DIR__ . '/SessionContainerHelper.php';

/**
 * Class for using File based Session Container
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class FileBasedSessionContainer extends SessionContainerHelper implements SessionContainerInterface
{
    public $sessionSavePath = null;

    public function init($sessionSavePath, $sessionName)
    {
        if (!is_dir($sessionSavePath)) {
            mkdir($sessionSavePath, 0755, true);
        }
        $this->sessionSavePath = $sessionSavePath;
        $this->currentTimestamp = time();
    }

    public function get($sessionId)
    {
        $filepath = $this->sessionSavePath . '/' . $sessionId;
        if (file_exists($filepath) && (($this->currentTimestamp - fileatime($filepath)) < $this->sessionMaxlifetime)) {
            return $this->decryptData(file_get_contents($filepath));
        }
        return false;
    }

    public function set($sessionId, $sessionData)
    {
        $filepath = $this->sessionSavePath . '/' . $sessionId;
        if (!file_exists($filepath)) {
            touch($filepath);
        }
        return file_put_contents($filepath, $this->encryptData($sessionData));
    }

    public function touch($sessionId, $sessionData)
    {
        $filepath = $this->sessionSavePath . '/' . $sessionId;
        return touch($filepath);
    }

    public function gc($sessionMaxlifetime)
    {
        $datetime = date('Y-m-d H:i', ($this->currentTimestamp - $sessionMaxlifetime));
        shell_exec("find {$this->sessionSavePath} -type f -not -newermt '{$datetime}' -delete");
        return true;
    }

    public function delete($sessionId)
    {
        $filepath = $this->sessionSavePath . '/' . $sessionId;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        return true;
    }
}
