<?php
/**
 * Class for using Session Handlers.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class Session
{
    /** SET THESE TO ENABLE ENCRYPTION */
    /** base64_encode(openssl_random_pseudo_bytes(32)) */
    // Example: static private $ENCRYPTION_PASS_PHRASE = 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
    static private $ENCRYPTION_PASS_PHRASE = null;
    /** base64_encode(openssl_random_pseudo_bytes(16)) */
    // Example: static private $ENCRYPTION_IV = 'HnPG5az9Xaxam9G9tMuRaw==';
    static private $ENCRYPTION_IV = null;
    
    /** MySql Session config */
    static private $DB_HOSTNAME = 'localhost';
    static private $DB_PORT = 3306;
    static private $DB_USERNAME = 'root';
    static private $DB_PASSWORD = 'shames11';
    static private $DB_DATABASE = 'db_session';

    /** Redis Session config */
    static private $REDIS_HOSTNAME = 'localhost';
    static private $REDIS_PORT = 6379;
    static private $REDIS_USERNAME = 'ramesh';
    static private $REDIS_PASSWORD = 'shames11';
    static private $REDIS_DATABASE = 0;

    /** Memcached Session config */
    static private $MEMCACHED_HOSTNAME = 'localhost';
    static private $MEMCACHED_PORT = 11211;

    /** Session options */
    static private $sessionName = 'PHPSESSID';
    static private $sessionMaxlifetime = 30 * 60; //30 mins.

    /** File Session options */
    // static private $sessionPath = '/tmp';
    static private $sessionPath = '/Users/rameshjangid/homebrew/var/www/php-session/session-files';

    /** Session Handler mode */
    static private $sessionMode = null;

    /** Session argument */
    static private $options = null;

    /** Session handler */
    static private $sessionHandler = null;

    /**
     * Generates session options argument
     *
     * @return void
     */
    static private function setConfig()
    {
        switch(self::$sessionMode) {
            case 'MySql':
                self::$sessionHandler->DB_HOSTNAME = self::$DB_HOSTNAME;
                self::$sessionHandler->DB_PORT = self::$DB_PORT;
                self::$sessionHandler->DB_USERNAME = self::$DB_USERNAME;
                self::$sessionHandler->DB_PASSWORD = self::$DB_PASSWORD;
                self::$sessionHandler->DB_DATABASE = self::$DB_DATABASE;
                break;
            case 'Redis':
                self::$sessionHandler->REDIS_HOSTNAME = self::$REDIS_HOSTNAME;
                self::$sessionHandler->REDIS_PORT = self::$REDIS_PORT;
                self::$sessionHandler->REDIS_USERNAME = self::$REDIS_USERNAME;
                self::$sessionHandler->REDIS_PASSWORD = self::$REDIS_PASSWORD;
                self::$sessionHandler->REDIS_DATABASE = self::$REDIS_DATABASE;
                break;
            case 'Memcached':
                self::$sessionHandler->MEMCACHED_HOSTNAME = self::$MEMCACHED_HOSTNAME;
                self::$sessionHandler->MEMCACHED_PORT = self::$MEMCACHED_PORT;
                break;
            default:
                break;
        }
        self::$sessionHandler->sessionMaxlifetime = self::$sessionMaxlifetime;
        if (
            !empty(self::$ENCRYPTION_PASS_PHRASE) &&
            !empty(self::$ENCRYPTION_IV)
        ) {
            self::$sessionHandler->passphrase = base64_decode(self::$ENCRYPTION_PASS_PHRASE);
            self::$sessionHandler->iv = base64_decode(self::$ENCRYPTION_IV);    
        }
    }

    /**
     * Generates session options argument
     *
     * @return void
     */
    static private function setOptions()
    {
        self::$options = [ // always required.
            'use_strict_mode' => true,
            'name' => self::$sessionName,
            'serialize_handler' => 'php_serialize',
            'gc_maxlifetime' => self::$sessionMaxlifetime
        ];

        switch(self::$sessionMode) {
            case 'File':
                self::$options['save_path'] = self::$sessionPath;
                break;
            default:
                break;
        }
    }

    /**
     * Initialise session handler
     *
     * @param string $sessionMode File/MySql/Cookie
     * @return void
     */
    static public function initSessionHandler($sessionMode)
    {
        self::$sessionMode = $sessionMode;
        $sessionModeFileLocation = __DIR__ . '/'.self::$sessionMode.'BasedSessionHandler.php';
        include $sessionModeFileLocation;
        $handlerClassName = self::$sessionMode.'BasedSessionHandler';
        self::$sessionHandler = new $handlerClassName();
        self::setConfig();
        session_set_save_handler(self::$sessionHandler, true);
        self::setOptions();
    }

    /**
     * Start session in read only mode
     *
     * @return void
     */
    static public function start_readonly()
    {
        $options = self::$options;
        $options['read_and_close'] = true;

        // Change $_COOKIE to $_REQUEST for non-cookie based session
        if (isset($_COOKIE[self::$sessionName])) {
            return session_start($options);
        }
        return false;
    }

    /**
     * Start session in read/write mode
     *
     * @return void
     */
    static public function start_rw_mode()
    {
        return session_start(self::$options);
    }
}
