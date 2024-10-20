<?php
/**
 * Interface for Session Containers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
interface SessionContainerInterface
{
    public function init($sessionSavePath, $sessionName);

    public function get($sessionId);

    public function set($sessionId, $sessionData);

    public function touch($sessionId, $sessionData);

    public function gc($sessionMaxlifetime);

    public function delete($sessionId);
}
