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

namespace CustomSessionHandler\Containers;

/**
 * Custom Session Handler Helper
 * php version 7
 *
 * @category  CustomSessionHandler_Helper
 * @package   CustomSessionHandler
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Session-Handlers
 * @since     Class available since Release 1.0.0
 */
class SessionContainerHelper
{
    // The cipher method
    private $cipher_algo = 'AES-256-CBC';

    // Bitwise disjunction of the flags OPENSSL_RAW_DATA,
    // and OPENSSL_ZERO_PADDING or OPENSSL_DON'T_ZERO_PAD_KEY */
    private $options = OPENSSL_RAW_DATA;

    // Usually 256-bit passphrase
    public $passphrase = null;

    // Usually 128-bit iv
    public $iv = null;

    // Current timestamp
    public $currentTimestamp = null;

    // Session cookie name
    public $sessionName = null;

    // Session data cookie name
    public $sessionDataName = null;

    // Session timeout
    public $sessionMaxLifetime = null;

    /**
     * Encryption
     *
     * @param string $plainText Plain Text
     *
     * @return string
     */
    protected function encryptData($plainText): string
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return base64_encode(
                string: openssl_encrypt(
                    data: $plainText,
                    cipher_algo: $this->cipher_algo,
                    passphrase: $this->passphrase,
                    options: $this->options,
                    iv: $this->iv
                )
            );
        }
        return $plainText;
    }

    /**
     * Decryption
     *
     * @param string $cipherText Cipher Text
     *
     * @return bool|string
     */
    protected function decryptData($cipherText): bool|string
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return openssl_decrypt(
                data: base64_decode(string: $cipherText),
                cipher_algo: $this->cipher_algo,
                passphrase: $this->passphrase,
                options: $this->options,
                iv: $this->iv
            );
        }
        return $cipherText;
    }
}
