<?php
/**
 * Interface for Session Containers
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
    /** For Custom Session Handler open callback method */
    public function init($sessionSavePath, $sessionName);

    /** For Custom Session Handler validateId callback method */
    public function get($sessionId);

    /** For Custom Session Handler write callback method */
    public function set($sessionId, $sessionData);

    /** For Custom Session Handler updateTimestamp callback method */
    public function touch($sessionId, $sessionData);

    /** For Custom Session Handler gc callback method */
    public function gc($sessionMaxlifetime);

    /** For Custom Session Handler destroy callback method */
    public function delete($sessionId);
}
