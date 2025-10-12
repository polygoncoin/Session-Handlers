<?php

/**
 * Custom Session Handler
 * php version 7
 *
 * @category  SessionHandler
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace CustomSessionHandler;

use CustomSessionHandler\CustomSessionHandler;
use CustomSessionHandler\Containers\SessionContainerInterface;

/**
 * Custom Session Handler Config
 * php version 7
 *
 * @category  CustomSessionHandler_Config
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class Session
{
    /**
     * SET THESE TO ENABLE ENCRYPTION
     * ENCRYPTION PASS PHRASE
     *
     * Value = base64_encode(openssl_random_pseudo_bytes(32))
     * Example: public static $ENCRYPTION_PASS_PHRASE =
     * 'H7OO2m3qe9pHyAHFiERlYJKnlTMtCJs9ZbGphX9NO/c=';
     *
     * @var null|string
     */
    public static $ENCRYPTION_PASS_PHRASE = null;

    /**
     * SET THESE TO ENABLE ENCRYPTION
     * ENCRYPTION IV
     *
     * Value = base64_encode(openssl_random_pseudo_bytes(16))
     * Example: public static $ENCRYPTION_IV = 'HnPG5az9Xaxam9G9tMuRaw==';
     *
     * @var null|string
     */
    public static $ENCRYPTION_IV = null;

    /* MySql Session config */
    public static $MYSQL_HOSTNAME = 'localhost';
    public static $MYSQL_PORT = 3306;
    public static $MYSQL_USERNAME = 'root';
    public static $MYSQL_PASSWORD = 'shames11';
    public static $MYSQL_DATABASE = 'session_db';
    public static $MYSQL_TABLE = 'sessions';

    /* MongoDb Session config */
    public static $MONGODB_HOSTNAME = 'localhost';
    public static $MONGODB_PORT = 27017;
    public static $MONGODB_USERNAME = 'ramesh';
    public static $MONGODB_PASSWORD = 'shames11';
    public static $MONGODB_DATABASE = 'session_db';
    public static $MONGODB_COLLECTION = 'sessions';

    /* Redis Session config */
    public static $REDIS_HOSTNAME = 'localhost';
    public static $REDIS_PORT = 6379;
    public static $REDIS_USERNAME = 'ramesh';
    public static $REDIS_PASSWORD = 'shames11';
    public static $REDIS_DATABASE = 0;

    /* Memcached Session config */
    public static $MEMCACHED_HOSTNAME = 'localhost';
    public static $MEMCACHED_PORT = 11211;

    /**
     * Session Id Cookie name
     *
     * @var string
     */
    public static $sessionName = 'PHPSESSID'; // Default

    /**
     * Session Data Cookie name; For cookie as container
     *
     * @var string
     */
    public static $sessionDataName = 'PHPSESSDATA';

    /**
     * Session Life
     *
     * @var integer
     */
    public static $sessionMaxLifetime = 30 * 60; // 30 mins.

    /**
     * File Session options
     * Example: public static $sessionSavePath = '/tmp';
     *
     * @var null|string
     */
    public static $sessionSavePath = null;

    /**
     * Session Handler mode
     *
     * @var null|string
     */
    public static $sessionMode = null;

    /**
     * Session Start function argument
     *
     * @var null|array
     */
    public static $options = null;

    /**
     * Session handler Container
     *
     * @var null|SessionContainerInterface
     */
    public static $sessionContainer = null;

    /**
     * Validate settings
     *
     * @return void
     */
    private static function validateSettings(): void
    {
        // sessionMode validation
        if (
            !in_array(
                needle: self::$sessionMode,
                haystack: ['File', 'MySql',  'MongoDb', 'Redis', 'Memcached', 'Cookie']
            )
        ) {
            die('Invalid "sessionMode"');
        }

        // Required param validations
        if (empty(self::$sessionName)) {
            die('Invalid "sessionName"');
        }
        if (self::$sessionMode === 'Cookie' && empty(self::$sessionDataName)) {
            die('Invalid "sessionDataName"');
        }
        if (empty(self::$sessionMaxLifetime)) {
            die('Invalid "sessionMaxLifetime"');
        }

        // Required parameters as per sessionMode
        switch (self::$sessionMode) {
            case 'Cookie':
                // Encryption compulsary for saving data as cookie
                if (empty(self::$ENCRYPTION_PASS_PHRASE)) {
                    die('Invalid "ENCRYPTION_PASS_PHRASE"');
                }
                if (empty(self::$ENCRYPTION_IV)) {
                    die('Invalid "ENCRYPTION_IV"');
                }
                break;
            case 'MySql':
                if (empty(self::$MYSQL_HOSTNAME)) {
                    die('Invalid "MYSQL_HOSTNAME"');
                }
                if (empty(self::$MYSQL_PORT)) {
                    die('Invalid "MYSQL_PORT"');
                }
                if (empty(self::$MYSQL_USERNAME)) {
                    die('Invalid "MYSQL_USERNAME"');
                }
                if (empty(self::$MYSQL_PASSWORD)) {
                    die('Invalid "MYSQL_PASSWORD"');
                }
                if (empty(self::$MYSQL_DATABASE)) {
                    die('Invalid "MYSQL_DATABASE"');
                }
                if (empty(self::$MYSQL_TABLE)) {
                    die('Invalid "MYSQL_TABLE"');
                }
                break;
            case 'MongoDb':
                if (empty(self::$MONGODB_HOSTNAME)) {
                    die('Invalid "MONGODB_HOSTNAME"');
                }
                if (empty(self::$MONGODB_PORT)) {
                    die('Invalid "MONGODB_PORT"');
                }
                if (empty(self::$MONGODB_DATABASE)) {
                    die('Invalid "MONGODB_DATABASE"');
                }
                if (empty(self::$MONGODB_COLLECTION)) {
                    die('Invalid "MONGODB_COLLECTION"');
                }
                break;
            case 'Redis':
                if (empty(self::$REDIS_HOSTNAME)) {
                    die('Invalid "REDIS_HOSTNAME"');
                }
                if (empty(self::$REDIS_PORT)) {
                    die('Invalid "REDIS_PORT"');
                }
                if (empty(self::$REDIS_USERNAME)) {
                    die('Invalid "REDIS_USERNAME"');
                }
                if (empty(self::$REDIS_PASSWORD)) {
                    die('Invalid "REDIS_PASSWORD"');
                }
                if (empty(self::$REDIS_DATABASE) && self::$REDIS_DATABASE != 0) {
                    die('Invalid "REDIS_DATABASE"');
                }
                break;
            case 'Memcached':
                if (empty(self::$MEMCACHED_HOSTNAME)) {
                    die('Invalid "MEMCACHED_HOSTNAME"');
                }
                if (empty(self::$MEMCACHED_PORT)) {
                    die('Invalid "MEMCACHED_PORT"');
                }
                break;
        }
    }

    /**
     * Initialize container
     *
     * @return void
     */
    private static function initContainer(): void
    {
        // Initialize Container
        $containerClassName = 'CustomSessionHandler\\Containers\\' .
            self::$sessionMode . 'BasedSessionContainer';
        self::$sessionContainer = new $containerClassName();

        // Setting required common parameters
        self::$sessionContainer->sessionName = self::$sessionName;
        self::$sessionContainer->sessionMaxLifetime = self::$sessionMaxLifetime;

        // Setting required parameters as per sessionMode
        switch (self::$sessionMode) {
            case 'MySql':
                self::$sessionContainer->MYSQL_HOSTNAME = self::$MYSQL_HOSTNAME;
                self::$sessionContainer->MYSQL_PORT = self::$MYSQL_PORT;
                self::$sessionContainer->MYSQL_USERNAME = self::$MYSQL_USERNAME;
                self::$sessionContainer->MYSQL_PASSWORD = self::$MYSQL_PASSWORD;
                self::$sessionContainer->MYSQL_DATABASE = self::$MYSQL_DATABASE;
                self::$sessionContainer->MYSQL_TABLE = self::$MYSQL_TABLE;
                break;
            case 'MongoDb':
                self::$sessionContainer->MONGODB_HOSTNAME = self::$MONGODB_HOSTNAME;
                self::$sessionContainer->MONGODB_PORT = self::$MONGODB_PORT;
                self::$sessionContainer->MONGODB_USERNAME = self::$MONGODB_USERNAME;
                self::$sessionContainer->MONGODB_PASSWORD = self::$MONGODB_PASSWORD;
                self::$sessionContainer->MONGODB_DATABASE = self::$MONGODB_DATABASE;
                self::$sessionContainer->MONGODB_COLLECTION = self::$MONGODB_COLLECTION;
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
            !empty(self::$ENCRYPTION_PASS_PHRASE)
            && !empty(self::$ENCRYPTION_IV)
        ) {
            self::$sessionContainer->passphrase = base64_decode(
                string: self::$ENCRYPTION_PASS_PHRASE
            );
            self::$sessionContainer->iv = base64_decode(
                string: self::$ENCRYPTION_IV
            );
        }
    }

    /**
     * Initialize session_set_save_handler process
     *
     * @return void
     */
    private static function initProcess(): void
    {
        // Initialize container
        self::initContainer();

        $customSessionHandler = new CustomSessionHandler(
            container: self::$sessionContainer
        );
        $customSessionHandler->sessionName = self::$sessionName;
        if (self::$sessionMode === 'Cookie') {
            $customSessionHandler->sessionDataName = self::$sessionDataName;
        }
        session_set_save_handler(open: $customSessionHandler, close: true);
    }

    /**
     * Generates session options argument
     *
     * @param array $options Options
     *
     * @return void
     */
    private static function setOptions($options = []): void
    {
        if (isset($options['name'])) {
            self::$sessionName = $options['name'];
        }

        if (isset($options['gc_maxlifetime'])) {
            self::$sessionMaxLifetime = $options['gc_maxlifetime'];
        }

        self::$options = [ // always required.
            'use_strict_mode' => true,
            'name' => self::$sessionName,
            'serialize_handler' => 'php_serialize',
            'lazy_write' => true,
            'gc_maxlifetime' => self::$sessionMaxLifetime,
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => ((strpos(
                haystack: $_SERVER['HTTP_HOST'],
                needle: 'localhost'
            ) === false) ? true : false),
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict'
        ];

        if (self::$sessionMode === 'File') {
            self::$options['save_path'] = self::$sessionSavePath;
        }

        if (!empty($options)) {
            foreach ($options as $key => $value) {
                if (
                    in_array(
                        needle: $key,
                        haystack: ['name', 'serialize_handler', 'gc_maxlifetime']
                    )
                ) {
                    // Skip these keys
                    continue;
                }
                self::$options[$key] = $value;
            }
        }
    }

    /**
     * Initialize session handler
     *
     * @param string $sessionMode File/MySql/Cookie
     * @param array  $options     Options
     *
     * @return void
     */
    public static function initSessionHandler($sessionMode, $options = []): void
    {
        self::$sessionMode = $sessionMode;

        // Set options from php.ini if not set in this class
        if (empty(self::$sessionName)) {
            self::$sessionName = session_name();
        }
        if (self::$sessionMode === 'File' && empty(self::$sessionSavePath)) {
            self::$sessionSavePath = (session_save_path() ?
                session_save_path() : sys_get_temp_dir()) . '/session-files';
        }

        // Comment this call once you are done with validating settings part
        self::validateSettings();

        // Initialize
        self::setOptions(options: $options);
        self::initProcess();
    }

    /**
     * Start session in read only mode
     *
     * @return void
     */
    public static function sessionStartReadonly(): bool
    {
        if (
            isset($_COOKIE[self::$sessionName])
            && !empty($_COOKIE[self::$sessionName])
        ) {
            $options = self::$options;
            $options['read_and_close'] = true;

            return session_start(options: $options);
        }
        return false;
    }

    /**
     * Start session in read/write mode
     *
     * @return bool
     */
    public static function sessionStartReadWrite(): bool
    {
        return session_start(options: self::$options);
    }
}
