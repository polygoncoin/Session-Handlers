<?php
/**
 * Class for using Cookie to managing session data with encryption
 * 
 * @category   Session
 * @package    Session Handlers
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class SessionContainerHelper
{
    /** The cipher method */
    private $cipher_algo = 'AES-256-CBC';

    /** Bitwise disjunction of the flags OPENSSL_RAW_DATA, and OPENSSL_ZERO_PADDING or OPENSSL_DONT_ZERO_PAD_KEY */
    private $options = OPENSSL_RAW_DATA;

    /** Usually 256-bit passphrase */
    public $passphrase = null;

    /** Usually 128-bit iv */
    public $iv = null;

    /** Current timestamp */
    public $currentTimestamp = null;

    /** Session cookie name */
    public $sessionName = null;

    /** Session data cookie name */
    public $sessionDataName = null;

    /** Session timeout */
    public $sessionMaxlifetime = null;

    /**
     * Encryption
     *
     * @param string $plaintext
     * @return string ciphertext
     */
    protected function encryptData($plaintext)
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return base64_encode(openssl_encrypt(
                $plaintext,
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            ));
        }
        return $plaintext;
    }

    /**
     * Decryption
     *
     * @param string $ciphertext
     * @return string plaintext
     */
    protected function decryptData($ciphertext)
    {
        if (!empty($this->passphrase) && !empty($this->iv)) {
            return openssl_decrypt(
                base64_decode($ciphertext),
                $this->cipher_algo,
                $this->passphrase,
                $this->options,
                $this->iv
            );
        }
        return $ciphertext;
    }
}
