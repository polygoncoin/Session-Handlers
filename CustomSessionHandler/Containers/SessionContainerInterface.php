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
    /** For Custom Session Handler - Initialize session */
    public function init($sessionSavePath, $sessionName);

    /** For Custom Session Handler - Validate session ID */
    public function get($sessionId);

    /** For Custom Session Handler - Write session data */
    public function set($sessionId, $sessionData);

    /** For Custom Session Handler - Update session timestamp */
    public function touch($sessionId, $sessionData);

    /** For Custom Session Handler - Cleanup old sessions */
    public function gc($sessionMaxlifetime);

    /** For Custom Session Handler - Destroy a session */
    public function delete($sessionId);
}
