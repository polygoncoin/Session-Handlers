<?php
require_once __DIR__ . '/CustomSessionHandler.php';

/**
 * Class for using Session Handlers
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
    /**
     * SET THESE TO ENABLE ENCRYPTION
     * ENCRYPTION PASS PHRASE
     * 
     * Value = base64_encode(openssl_random_pseudo_bytes(32))
     * Example: static private $ENCRYPTION_PASS_PHRASE = 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
     * 
     * @var null|string
     */
    static private $ENCRYPTION_PASS_PHRASE = null;

    /**
     * SET THESE TO ENABLE ENCRYPTION
     * ENCRYPTION IV
     * 
     * Value = base64_encode(openssl_random_pseudo_bytes(16))
     * Example: static private $ENCRYPTION_IV = 'HnPG5az9Xaxam9G9tMuRaw==';
     * 
     * @var null|string
     */
    static private $ENCRYPTION_IV = null;
    
    /** MySql Session config */
    static private $DB_HOSTNAME = 'localhost';
    static private $DB_PORT = 3306;
    static private $DB_USERNAME = 'root';
    static private $DB_PASSWORD = 'shames11';
    static private $DB_DATABASE = 'db_session';
    static private $DB_TABLE = 'sessions';

    /** Redis Session config */
    static private $REDIS_HOSTNAME = 'localhost';
    static private $REDIS_PORT = 6379;
    static private $REDIS_USERNAME = 'ramesh';
    static private $REDIS_PASSWORD = 'shames11';
    static private $REDIS_DATABASE = 0;

    /** Memcached Session config */
    static private $MEMCACHED_HOSTNAME = 'localhost';
    static private $MEMCACHED_PORT = 11211;

    /**
     * Session Id Cookie name
     * 
     * @var string
     */
    static private $sessionName = 'PHPSESSID'; // Default

    /**
     * Session Data Cookie name; For cookie as container
     * 
     * @var string
     */
    static private $sessionDataName = 'PHPSESSDATA';
    
    /**
     * Session Life
     * 
     * @var integer
     */
    static private $sessionMaxlifetime = 30 * 60; // 30 mins.

    /**
     * File Session options
     * Example: static private $sessionSavePath = '/tmp';
     * 
     * @var null|string
     */
    static private $sessionSavePath = null;

    /**
     * Session Handler mode
     * 
     * @var null|string
     */
    static private $sessionMode = null;

    /**
     * session_start argument
     * 
     * @var null|array
     */
    static private $options = null;

    /**
     * Session handler Container
     * 
     * @var null|SessionContainerInterface
     */
    static private $sessionContainer = null;

    /**
     * Validate settings
     *
     * @return void
     */
    static private function validateSettings()
    {
        // sessionMode validation
        if (!in_array(self::$sessionMode, ['File', 'MySql', 'Redis', 'Memcached', 'Cookie'])) {
            die('Invalid "sessionMode"');
        }

        // Required param validations
        if (empty(self::$sessionName)) {
            die('Invalid "sessionName"');
        }
        if (self::$sessionMode === 'Cookie' && empty(self::$sessionDataName)) {
            die('Invalid "sessionDataName"');
        }
        if (empty(self::$sessionMaxlifetime)) die('Invalid "sessionMaxlifetime"');

        // Required parameters as per sessionMode
        switch(self::$sessionMode) {
            case 'Cookie':
                // Encryption compulsary for saving data as cookie
                if (empty(self::$ENCRYPTION_PASS_PHRASE)) die('Invalid "ENCRYPTION_PASS_PHRASE"');
                if (empty(self::$ENCRYPTION_IV)) die('Invalid "ENCRYPTION_IV"');
                break;
            case 'MySql':
                if (empty(self::$DB_HOSTNAME)) die('Invalid "DB_HOSTNAME"');
                if (empty(self::$DB_PORT)) die('Invalid "DB_PORT"');
                if (empty(self::$DB_USERNAME)) die('Invalid "DB_USERNAME"');
                if (empty(self::$DB_PASSWORD)) die('Invalid "DB_PASSWORD"');
                if (empty(self::$DB_DATABASE)) die('Invalid "DB_DATABASE"');
                if (empty(self::$DB_TABLE)) die('Invalid "DB_TABLE"');
                break;
            case 'Redis':
                if (empty(self::$REDIS_HOSTNAME)) die('Invalid "REDIS_HOSTNAME"');
                if (empty(self::$REDIS_PORT)) die('Invalid "REDIS_PORT"');
                if (empty(self::$REDIS_USERNAME)) die('Invalid "REDIS_USERNAME"');
                if (empty(self::$REDIS_PASSWORD)) die('Invalid "REDIS_PASSWORD"');
                if (empty(self::$REDIS_DATABASE) && self::$REDIS_DATABASE!=0) {
                    die('Invalid "REDIS_DATABASE"');
                }
                break;
            case 'Memcached':
                if (empty(self::$MEMCACHED_HOSTNAME)) die('Invalid "MEMCACHED_HOSTNAME"');
                if (empty(self::$MEMCACHED_PORT)) die('Invalid "MEMCACHED_PORT"');
                break;
        }
    }

    /**
     * Initialise container
     *
     * @return void
     */
    static private function initContainer()
    {
        // Container initialisation
        $sessionContainerFileLocation = __DIR__ . '/Containers/'.self::$sessionMode.'BasedSessionContainer.php';
        if (!file_exists($sessionContainerFileLocation)) {
            die('Missing file:'.$sessionContainerFileLocation);
        }
        include_once $sessionContainerFileLocation;
        $containerClassName = self::$sessionMode.'BasedSessionContainer';
        self::$sessionContainer = new $containerClassName();

        // Setting required common parameters
        self::$sessionContainer->sessionName = self::$sessionName;
        self::$sessionContainer->sessionMaxlifetime = self::$sessionMaxlifetime;

        // Setting required parameters as per sessionMode
        switch(self::$sessionMode) {
            case 'MySql':
                self::$sessionContainer->DB_HOSTNAME = self::$DB_HOSTNAME;
                self::$sessionContainer->DB_PORT = self::$DB_PORT;
                self::$sessionContainer->DB_USERNAME = self::$DB_USERNAME;
                self::$sessionContainer->DB_PASSWORD = self::$DB_PASSWORD;
                self::$sessionContainer->DB_DATABASE = self::$DB_DATABASE;
                self::$sessionContainer->DB_TABLE = self::$DB_TABLE;
                break;
            case 'Redis':
                self::$sessionContainer->REDIS_HOSTNAME = self::$REDIS_HOSTNAME;
                self::$sessionContainer->REDIS_PORT = self::$REDIS_PORT;
                self::$sessionContainer->REDIS_USERNAME = self::$REDIS_USERNAME;
                self::$sessionContainer->REDIS_PASSWORD = self::$REDIS_PASSWORD;
                self::$sessionContainer->REDIS_DATABASE = self::$REDIS_DATABASE;
                break;
            case 'Memcached':
                self::$sessionContainer->MEMCACHED_HOSTNAME = self::$MEMCACHED_HOSTNAME;
                self::$sessionContainer->MEMCACHED_PORT = self::$MEMCACHED_PORT;
                break;
            case 'Cookie':
                self::$sessionContainer->sessionDataName = self::$sessionDataName;
                break;
        }

        // Setting encryption parameters
        if (
            !empty(self::$ENCRYPTION_PASS_PHRASE) &&
            !empty(self::$ENCRYPTION_IV)
        ) {
            self::$sessionContainer->passphrase = base64_decode(self::$ENCRYPTION_PASS_PHRASE);
            self::$sessionContainer->iv = base64_decode(self::$ENCRYPTION_IV);    
        }
    }

    /**
     * Initialise session_set_save_handler process
     *
     * @return void
     */
    static private function initProcess()
    {
        // Initialise container
        self::initContainer();

        $customSessionHandler = new CustomSessionHandler(self::$sessionContainer);
        $customSessionHandler->sessionName = self::$sessionName;
        if (self::$sessionMode === 'Cookie') {
            $customSessionHandler->sessionDataName = self::$sessionDataName;
        }
        session_set_save_handler($customSessionHandler, true);
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
            'lazy_write' => true,
            'gc_maxlifetime' => self::$sessionMaxlifetime,
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => ((strpos($_SERVER['HTTP_HOST'], 'localhost') === false) ? true : false),
            'cookie_httponly' => true,
            'cookie_samesite' => 'LAX'
        ];

        if (self::$sessionMode === 'File') {
            self::$options['save_path'] = self::$sessionSavePath;
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

        // Set options from php.ini if not set in this class
        if (empty(self::$sessionName)) {
            self::$sessionName = session_name();
        }
        if (self::$sessionMode === 'File' && empty(self::$sessionSavePath)) {
            self::$sessionSavePath = (session_save_path() ? session_save_path() : sys_get_temp_dir()) . '/session-files';
        }

        // Comment this call once you are done with validating settings part
        self::validateSettings();

        // Initalise
        self::initProcess();
        self::setOptions();
    }

    /**
     * Start session in read only mode
     *
     * @return void
     */
    static public function start_readonly()
    {
        if (isset($_COOKIE[self::$sessionName]) && !empty($_COOKIE[self::$sessionName])) {
            $options = self::$options;
            $options['read_and_close'] = true;
    
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
