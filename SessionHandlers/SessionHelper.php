<?php
/**
 * Class for using Cookie to managing session data with encryption.
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class SessionHelper
{
    /** The cipher method */
    private $cipher_algo = 'AES-256-CBC';

    /** Usually 256-bit passphrase */
    public $passphrase = null;

    /** Bitwise disjunction of the flags OPENSSL_RAW_DATA, and OPENSSL_ZERO_PADDING or OPENSSL_DONT_ZERO_PAD_KEY */
    private $options = OPENSSL_RAW_DATA;

    /** Usually 128-bit iv */
    public $iv = null;

    /**
     * Encryption
     *
     * @param string $plaintext
     * @return string ciphertext
     */
    function encryptData($plaintext)
    {
        if (!empty($this->passphrase)) {
            return openssl_encrypt(
                $plaintext,
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            );
        }
        return $plaintext;
    }

    /**
     * Decryption
     *
     * @param string $ciphertext
     * @return string plaintext
     */
    function decryptData($ciphertext)
    {
        if (!empty($this->passphrase)) {
            return openssl_decrypt(
                $ciphertext,
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            );
        }
        return $ciphertext;
    }

    /**
     * Returns random 64 char string
     *
     * @return string
     */
    function getRandomString()
    {
        return bin2hex(random_bytes(32));
    }
}
