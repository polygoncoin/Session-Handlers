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
    /**
     * For Custom Session Handler - Initialize session
     *
     * @param string $sessionSavePath
     * @param string $sessionName
     * @return void
    */
    public function init($sessionSavePath, $sessionName);

    /**
     * For Custom Session Handler - Validate session ID
     *
     * @param string $sessionId
     * @return boolean|string
     */
    public function get($sessionId);

    /**
     * For Custom Session Handler - Write session data
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean
     */
    public function set($sessionId, $sessionData);

    /**
     * For Custom Session Handler - Update session timestamp
     *
     * @param string $sessionId
     * @param string $sessionData
     * @return boolean
     */
    public function touch($sessionId, $sessionData);

    /**
     * For Custom Session Handler - Cleanup old sessions
     *
     * @param integer $sessionMaxlifetime
     * @return boolean
     */
    public function gc($sessionMaxlifetime);

    /**
     * For Custom Session Handler - Destroy a session
     *
     * @param string $sessionId
     * @return boolean
     */
    public function delete($sessionId);

    /**
     * For Custom Session Handler - Close container connection
     *
     * @return void
     */
    public function close();
}
