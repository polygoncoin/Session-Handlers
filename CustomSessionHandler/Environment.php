<?php

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */

namespace CustomSessionHandler;

/**
 * Environment
 * php version 8.3
 *
 * @category  Environment
 * @package   CustomSessionHandler
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class Environment
{
    public $timestamp = null;

    public $SESSION_STORE_MODE = null;

    public $SESSION_LIFETIME = null;
    public $SESSION_STORE_PATH = null;
    public $SESSION_COOKIE_NAME = null;
    public $SESSION_COOKIE_PATH = null;
    public $SESSION_COOKIE_SECURE = null;
    public $SESSION_COOKIE_HTTPONLY = null;
    public $SESSION_COOKIE_SAMESITE = null;

    public $SESSION_ENCRYPTION_PASS_PHRASE = null;
    public $SESSION_ENCRYPTION_IV = null;

    public $SESSION_MYSQL_HOST = null;
    public $SESSION_MYSQL_PORT = null;
    public $SESSION_MYSQL_USER = null;
    public $SESSION_MYSQL_PASSWORD = null;
    public $SESSION_MYSQL_DB = null;
    public $SESSION_MYSQL_TABLE = null;

    public $SESSION_PGSQL_HOST = null;
    public $SESSION_PGSQL_PORT = null;
    public $SESSION_PGSQL_USER = null;
    public $SESSION_PGSQL_PASSWORD = null;
    public $SESSION_PGSQL_DB = null;
    public $SESSION_PGSQL_TABLE = null;

    public $SESSION_MONGO_HOST = null;
    public $SESSION_MONGO_PORT = null;
    public $SESSION_MONGO_USER = null;
    public $SESSION_MONGO_PASSWORD = null;
    public $SESSION_MONGO_DB = null;
    public $SESSION_MONGO_TABLE = null;

    public $SESSION_REDIS_HOST = null;
    public $SESSION_REDIS_PORT = null;
    public $SESSION_REDIS_USER = null;
    public $SESSION_REDIS_PASSWORD = null;
    public $SESSION_REDIS_DB = null;

    public $SESSION_MEMCACHED_HOST = null;
    public $SESSION_MEMCACHED_PORT = null;

    public $SESSION_DATA_COOKIE_NAME = null;

	/**
	 * Constructor
	 * 
	 * @param string $envFile Enviroment file name
	 */
	public function __construct($envFile)
	{
        $this->timestamp = time();

        $ROOT = realpath(path: __DIR__ . '/..');
        $envDataArray = parse_ini_file(
            filename: $ROOT . DIRECTORY_SEPARATOR . $envFile
        );
        
        foreach ($envDataArray as $envVarName => $envVarValue) {
            $this->$envVarName = $envVarValue;
        }
	}
}
